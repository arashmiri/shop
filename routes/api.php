<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProductController as PublicProductController;  // برای کنترلر عمومی محصولات
use App\Http\Controllers\Vendor\ProductController as VendorProductController; // برای کنترلر فروشندگان
use App\Http\Controllers\ReviewController;

Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
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
    Route::post('/vendor/products', [VendorProductController::class, 'store']); // ایجاد محصول
    Route::get('/vendor/products', [VendorProductController::class, 'index']); // لیست محصولات
    Route::put('/vendor/products/{productId}', [VendorProductController::class, 'update']); // ویرایش محصول
    Route::delete('/vendor/products/{productId}', [VendorProductController::class, 'destroy']); // حذف محصول
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']); // ارسال نظر
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'show']); // دریافت نظرات محصول
});

Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{id}', [PublicProductController::class, 'show']);

