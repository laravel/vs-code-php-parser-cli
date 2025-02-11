<?php

namespace App\Commands;

use Vendor\Package\Thing;
use Vendor\Package\Contracts\BigContract;
use Vendor\Package\Support\Contracts\SmallContract;
use App\Models\User;

class MyCommand extends Thing implements BigContract, SmallContract
{
    protected User $user;

    public function also(array $hm)
    {
    }

    public function render(array $params)
    {
        User::where('name', 'Joe')->with([
            'team'=> function($q) {
                $q->where('name', 'Manager');
            },
            'org' => function ($q) {
                $q->where('company'
            // 'team'=> fn($q) =>$q->where('name', 'Manager'),
            // 'org' => fn ($q) => $q->where('company'
