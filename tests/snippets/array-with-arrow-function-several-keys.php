<?php

use App\Models\User;
use Illuminate\Database\Query\Builder;

User::with([
    'team' => fn(Builder $q) => $q->where('',''),
    'organization' => fn($q) => $q->whereIn('
