<?php

use App\Models\User;
use Illuminate\Database\Query\Builder;

Route::get('/', function () {
    $user = new User(['
