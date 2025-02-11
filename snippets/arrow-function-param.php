<?php

use App\Models\User;
use Illuminate\Database\Query\Builder;

User::where(fn(Builder $q) => $q->whereIn('
