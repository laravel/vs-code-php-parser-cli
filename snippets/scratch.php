<?php

// User::with(['first' => '', '

// User::with([
//     'records' => function($q) { $q->where('')->orWhere('

// User::where('email', 'whatever')->where(function ($q) {
//         $q->where('');
//     })->with([
//         'currentTeam' => function ($q) {
//             $q->where('');
//         },
//         '


User::where('email', 'whatever')->with([
        'currentTeam' =>  fn($q) => $q->where(''),
        '
