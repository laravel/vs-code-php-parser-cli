<?php

use App\Models\User;
use Illuminate\Database\Query\Builder;

User::where(function(Builder $q) {
    $q->whereIn('
