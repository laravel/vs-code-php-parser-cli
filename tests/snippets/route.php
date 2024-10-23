<?php

namespace App\Commands;

Route::get('what', function() {

});

Route::post('ok', function(User $user) {
    User::where('
