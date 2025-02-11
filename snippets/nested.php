<?php

use App\Models\User;
use Illuminate\Database\Query\Builder;

Route::get('/', function () {

    trans('auth.throttle');

    User::where('
