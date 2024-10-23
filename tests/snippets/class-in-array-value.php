<?php

use App\Models\User;
use Illuminate\Database\Query\Builder;

Route::get('/', function () {
    return Inertia::render('ProviderCreate', [
        'providers'       => Providers::all(),
        'isFirstProvider' => auth()->user()->providers()->count() === 0,
        'jsonPolicy'      => file_get_contents(resource_path('providers/aws/policy.json')),
    ]);
