<?php

namespace App\ResourceProviders;

use App\Data\IpSyncDiff;
use App\Data\ProviderFirewall;
use App\Data\ProviderRegion;
use App\Data\ProviderResource;
use App\Data\RuleValidation;
use App\Exceptions\FirewallNotFoundException;
use App\Models\Provider;
use App\Models\Rule;
use App\Models\RuleLog;
use App\Models\RuleSecurityGroup;
use App\Models\RuleSecurityGroupRule;
use App\ResourceProviders\Helpers\AWSHelper;
use App\ResourceProviders\Validators\AWSValidator;
use App\Util\Ip;
use Aws\CommandPool;
use Aws\Credentials\Credentials;
use Aws\Ec2\Exception\Ec2Exception;
use Aws\MultiRegionClient;
use Aws\ResultInterface;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use GuzzleHttp\Promise\CancellationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AWS extends AbstractProvider
{
    public $hasFirewalls = true;

    public $needsRegion = true;

    protected $client;

    protected AWSValidator $validator;


    public function __construct(Provider $provider)
    {
        $this->client = app(MultiRegionClient::class, [
            'args' => [
                'credentials' => new Credentials($provider->credentials['key'], $provider->credentials['secret']),
                'version'     => 'latest',
                'service'     => 'ec2',
            ],
        ]);

        $this->addedRules = collect();
        $this->dbProvider = $provider;
    }

    public function getSecurityGroupById(string $id, string $region = null): ProviderFirewall
    {
        $awsSecurityGroup = $this->client->describeSecurityGroups([
            'Filters' => [
                [
                    'Name'   => 'group-id',
                    'Values' => [$id],
                ],
            ],
            '@region' => $region,
        ]);

        if (!count($awsSecurityGroup->get('SecurityGroups'))) {
            return new ProviderFirewall(
                id: $id,
                name: '[Security Group not found]',
            );
        }

        return new ProviderFirewall(
            id: $awsSecurityGroup->get('SecurityGroups')[0]['GroupId'],
            name: $awsSecurityGroup->get('SecurityGroups')[0]['GroupName'],
        );
    }

    public function getProviderFirewallById(string $id, string $region = null): ProviderFirewall|false
    {
        return $this->getSecurityGroupById($id, $region);
    }

    public function test(): bool
    {
        try {
            $this->client->describeSecurityGroups([
                'DryRun'  => true,
                '@region' => 'us-east-1',
            ]);
        } catch (Ec2Exception $e) {
            // If we have the permissions, but it's a dry run, we're cool
            return $e->getAwsErrorCode() === 'DryRunOperation';
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function securityGroups(string $region): Collection
    {
        return collect($this->client->describeSecurityGroups([
            '@region' => $region,
        ])->get('SecurityGroups'))->map(
            fn ($group) => new ProviderFirewall(
                id: $group['GroupId'],
                name: $group['GroupName'],
                description: $group['Description'] ?: null,
            ),
        )->sortBy(fn ($f) => strtolower($f->name))->values();
    }

    /**
     * @return \Illuminate\Support\Collection<ProviderRegion>
     */
    public function regions(): Collection
    {
        $sdkRegionsPath = base_path('vendor/aws/aws-sdk-php/src/data/endpoints.json.php');

        if (!file_exists($sdkRegionsPath)) {
            throw new \Exception('SDK Regions path does not exist!');

            return collect();
        }

        $result = $this->client->describeRegions([
            '@region' => $this->dbProvider->default_region ?: 'us-east-1',
        ]);

        $sdkConfig = require $sdkRegionsPath;

        $sdkRegions = collect($sdkConfig['partitions'])->flatMap(fn ($p) => $p['regions']);

        return collect($result->get('Regions'))->map(fn ($r) => new ProviderRegion(
            id: $r['RegionName'],
            name: $sdkRegions->offsetGet($r['RegionName'])['description'],
        ))->sortBy('id')->values();
    }

    public function validateAddRule(RuleValidation $rule): void
    {
        $allowManagementIds = collect(request()->input('let_blip_manage_rule_ids'));

        // Let's do some integrity checking up front and inform user if we encounter anything instead of adding and rolling back
        $this->validator = new AWSValidator(
            client: $this->client,
            rule: $rule,
            allowManagementIds: $allowManagementIds,
        );

        $this->validator->validate();

        if ($this->validator->problems->count()) {
            throw ValidationException::withMessages([
                'existingRules' => $this->validator->problems,
            ]);
        }
    }

    public function getProviderResourceById(string $id, string $region = null): ProviderResource
    {
        $securityGroup = $this->getSecurityGroupById($id, $region);

        return new ProviderResource(
            id: $securityGroup->id,
            name: $securityGroup->name,
        );
    }

    public function getDefaultRegion(): ?string
    {
        // We're only looking for one, but must be an object so that
        // closure will reflect changes [sigh]
        $defaultRegion = collect();

        // Sort the US ones first to see if we can find a result faster, based on likely demographic
        $sortedRegions = $this->regions()->sortBy(fn ($r) => Str::startsWith($r->id, 'us-') ? -1 : 0);

        $pools = $sortedRegions
            ->map(fn (ProviderRegion $r) => $this->client->getCommand('DescribeSecurityGroups', [
                '@region'    => $r->id,
                'MaxResults' => 5,
            ]))
            ->chunk(10)
            ->map(fn ($commands) => new CommandPool($this->client, $commands, [
                'fulfilled' => function (
                    ResultInterface $result,
                    $iterKey,
                    PromiseInterface $aggregatePromise,
                ) use ($defaultRegion, $commands) {
                    if ($defaultRegion->count() !== 0) {
                        $aggregatePromise->cancel();
                        // We already found one, we're good
                        return;
                    }

                    // Let's try and find something with more than just the default security group, that's a sensible default
                    if (count($result->get('SecurityGroups')) > 1) {
                        $defaultRegion->push(
                            $commands->values()[$iterKey]->offsetGet('@region'),
                        );

                        $aggregatePromise->cancel();
                    }
                },
            ]));

        foreach ($pools as $pool) {
            try {
                $pool->promise()->wait();
            } catch (CancellationException $e) {
                // We're good, we cancelled the remaining
                // requests since we found one already
            }

            if ($defaultRegion->count() > 0) {
                return $defaultRegion->first();
            }
        }

        // Default to the first region, they can change it later
        return $sortedRegions->first()->id;
    }

    public function rollback(): void
    {
        $this->addedRules->each(function ($rule) {
            $this->client->revokeSecurityGroupIngress([
                'GroupId'              => $rule['security_group_id'],
                'SecurityGroupRuleIds' => [$rule['security_group_rule_id']],
                '@region'              => $rule['region'],
            ]);
        });
    }

    public function syncIpsToFirewall(
        string $firewallId,
        Collection $ips,
        string $protocol,
        string $port,
        string $ruleDescription,
        string $region = null,
        ?Collection $oldIps = null,
    ): IpSyncDiff {
        $result = $this->client->describeSecurityGroupRules([
            'Filters' => [
                [

                    'Name'   => 'group-id',
                    'Values' => [$firewallId],
                ],
            ],
            '@region' => $region,
        ]);

        $existingRules = collect($result->get('SecurityGroupRules'))
            ->filter(fn ($r) => !$r['IsEgress'])
            ->filter(fn ($r) => $r['IpProtocol'] === AWSHelper::getProtocol($protocol))
            ->filter(fn ($r) => (string) $r['FromPort'] === AWSHelper::getFromPort($port)
                && (string) $r['ToPort'] === AWSHelper::getToPort($port))
            ->values();

        $existingIpAddresses = $existingRules->map(fn ($r) => AWSHelper::getIpFromRule($r));

        $awsIps = $ips->map(fn ($ip) => AWSHelper::getIp($ip));
        $oldAwsIps = $oldIps->map(fn ($ip) => AWSHelper::getIp($ip));

        $toRemove = $existingRules->filter(
            fn ($r) => $oldAwsIps->contains(AWSHelper::getIpFromRule($r))
        )->values();
        $toAdd = $awsIps->diff($existingIpAddresses)->values();

        if ($toRemove->count() > 0) {
            $ruleIds = $toRemove->pluck('SecurityGroupRuleId');

            $this->client->revokeSecurityGroupIngress([
                'GroupId'              => $firewallId,
                'SecurityGroupRuleIds' => $ruleIds->toArray(),
                '@region'              => $region,
            ]);
        }

        if ($toAdd->count() > 0) {
            $params = [
                '@region'       => $region,
                'GroupId'       => $firewallId,
                'IpPermissions' => [
                    [
                        'IpProtocol' => AWSHelper::getProtocol($protocol),
                        'FromPort'   => AWSHelper::getFromPort($port),
                        'ToPort'     => AWSHelper::getToPort($port),
                        'IpRanges'   => $toAdd->filter(fn ($r) => Ip::isIpv4($r))->values()->map(fn ($ip) => [
                            'CidrIp'      => $ip,
                            'Description' => $ruleDescription,
                        ])->toArray(),
                        'Ipv6Ranges'   => $toAdd->filter(fn ($r) => Ip::isIpv6($r))->values()->map(fn ($ip) => [
                            'CidrIpv6'    => $ip,
                            'Description' => $ruleDescription,
                        ])->toArray(),
                    ],
                ],
            ];

            if (count($params['IpPermissions'][0]['IpRanges']) === 0) {
                unset($params['IpPermissions'][0]['IpRanges']);
            }

            if (count($params['IpPermissions'][0]['Ipv6Ranges']) === 0) {
                unset($params['IpPermissions'][0]['Ipv6Ranges']);
            }

            try {
                $this->client->authorizeSecurityGroupIngress($params);
            } catch (\Exception $e) {
                Bugsnag::notifyException($e);
            }
        }

        return new IpSyncDiff(
            added: $toAdd,
            removed: $toRemove->map(fn ($r) => AWSHelper::getIpFromRule($r)),
        );
    }

    protected function addFirewallRule(Rule $rule): Rule
    {
        $rule->securityGroups->each(function (RuleSecurityGroup $securityGroup) use ($rule) {
            $ruleName = $rule->name ?
                Str::of($rule->name)->pipe(fn ($s) => preg_replace('/[^\da-z ]/i', '', $s))->limit(100)->wrap('(', ')')->toString()
                : '';

            $params = [
                'GroupId'       => $securityGroup->security_group_id,
                'IpPermissions' => [
                    [
                        'IpProtocol' => AWSHelper::getProtocol($rule->protocol),
                        'FromPort'   => AWSHelper::getFromPort($rule->port),
                        'ToPort'     => AWSHelper::getToPort($rule->port),
                        'IpRanges'   => [
                            [
                                'CidrIp'      => AWSHelper::getIp($rule->ip_address),
                                'Description' => trim(
                                    sprintf(
                                        'Created by %s %s',
                                        config('app.name'),
                                        $ruleName,
                                    ),
                                ),
                            ],
                        ],
                    ],
                ],
                '@region' => $rule->region,
            ];

            try {
                $result = $this->client->authorizeSecurityGroupIngress($params);

                $this->addedRules->push([
                    'security_group_id'      => $securityGroup->security_group_id,
                    'security_group_rule_id' => $result->get('SecurityGroupRules')[0]['SecurityGroupRuleId'],
                    'region'                 => $rule->region,
                ]);

                // $securityGroup->rules()->save(new RuleSecurityGroupRule(['
                // $securityGroup->rules()->save('
                $securityGroup->save('
