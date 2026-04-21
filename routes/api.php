<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IpController;

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/ip/status', [IpController::class, 'status']);
    Route::post('/ip/unblock', [IpController::class, 'unblock']);
});