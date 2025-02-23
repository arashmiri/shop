<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    // مسیر لاگ اوت
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});
