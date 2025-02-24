<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\UserController;

Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    // مسیر لاگ اوت
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::get('/admin/vendors', [VendorController::class, 'index']);
});




Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/admin/upgrade-to-vendor', [VendorController::class, 'upgradeToVendor']);
});

