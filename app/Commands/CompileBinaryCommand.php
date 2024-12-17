<?php

namespace App\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;

class CompileBinaryCommand extends Command
{
    protected $signature = 'compile-binary {--arch=}';

    protected $description = 'Compile the binary for the current version';

    public function handle(): void
    {
        $timeout = 60 * 10;

        set_time_limit($timeout);

        $version = File::json(base_path('composer.json'))['version'];

        info("Compiling binary for version {$version}");

        $destination = base_path(
            sprintf('bin/php-parser-v%s-%s', $version, $this->option('arch'))
        );

        info("Destination: {$destination}");

        if (file_exists(base_path('.env'))) {
            exec('mv ' . base_path('.env') . ' ' . base_path('.env.bak'));
        }

        file_put_contents(base_path('.env'), '');

        exec('composer install --no-dev');

        $extensions = collect([
            'bcmath',
            'calendar',
            'ctype',
            'curl',
            'dba',
            'dom',
            'exif',
            'fileinfo',
            'filter',
            'iconv',
            'mbregex',
            'mbstring',
            'openssl',
            'pcntl',
            'pdo_mysql',
            'pdo_sqlite',
            'pdo',
            'phar',
            'posix',
            'readline',
            'session',
            'simplexml',
            'sockets',
            'sodium',
            'sqlite3',
            'tokenizer',
            'xml',
            'xmlreader',
            'xmlwriter',
            'zip',
            'zlib',
        ])->implode(',');

        $spc = base_path('spc');

        collect([
            base_path('php-parser') . " app:build --build-version={$version}",
            sprintf('%s download --with-php=8.2 --for-extensions="%s"', $spc, $extensions),
            sprintf('%s build --build-micro --build-cli "%s"', $spc, $extensions),
            sprintf('%s micro:combine %s -O %s', $spc, base_path('builds/php-parser'), $destination),
        ])->each(function (string $command) use ($timeout) {
            Process::timeout($timeout)->run($command, function (string $type, string $output) {
                echo $output;
            });
        });

        if (file_exists(base_path('.env.bak'))) {
            exec('mv ' . base_path('.env.bak') . ' ' . base_path('.env'));
        }

        info("Binary compiled successfully at {$destination}");
    }
}
