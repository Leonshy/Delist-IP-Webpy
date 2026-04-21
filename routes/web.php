<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('unblock', [
        'turnstileSiteKey' => config('services.turnstile.site_key', ''),
    ]);
});
