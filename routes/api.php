<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\vendor\ProductController;
use App\Http\Controllers\ReviewController;

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

Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    Route::post('/vendor/products', [ProductController::class, 'store']); // ایجاد محصول
    Route::get('/vendor/products', [ProductController::class, 'index']); // لیست محصولات
    Route::put('/vendor/products/{productId}', [ProductController::class, 'update']); // ویرایش محصول
    Route::delete('/vendor/products/{productId}', [ProductController::class, 'destroy']); // حذف محصول
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']); // ارسال نظر
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'show']); // دریافت نظرات محصول
});



