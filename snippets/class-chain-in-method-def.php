<?php

namespace App\Commands;

use Vendor\Package\Thing;
use Vendor\Package\Contracts\BigContract;
use Vendor\Package\Support\Contracts\SmallContract;
use App\Models\User;

class MyCommand extends Thing implements BigContract, SmallContract
{
    protected User $user;

    public function render(array $params)
    {
        User::where('url', $url)
            ->whereNotIn('ip_address1', $ipAddresses1->toArray())
            ->whereNotIn('ip_address2', $ipAddresses2->toArray())
            ->whereNotIn('ip_address3', $ipAddresses3->toArray())
            ->with('records', ['another'], ['my_key' => 'my_value'], '
