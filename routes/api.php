<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController as PublicProductController;  // برای کنترلر عمومی محصولات
use App\Http\Controllers\Vendor\ProductController as VendorProductController; // برای کنترلر فروشندگان
use App\Http\Controllers\Vendor\OrderController as VendorOrderController; // برای کنترلر سفارشات فروشندگان
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
    
    // Vendor order management routes
    Route::get('/vendor/orders', [VendorOrderController::class, 'index']); // لیست سفارشات فروشنده
    Route::get('/vendor/orders/{id}', [VendorOrderController::class, 'show']); // جزئیات سفارش فروشنده
    Route::put('/vendor/orders/{id}/status', [VendorOrderController::class, 'updateStatus']); // بروزرسانی وضعیت سفارش
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']); // ارسال نظر
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'show']); // دریافت نظرات محصول
    
    // Order routes
    Route::get('/orders', [OrderController::class, 'index']); // لیست سفارشات کاربر
    Route::get('/orders/{id}', [OrderController::class, 'show']); // جزئیات سفارش
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']); // لغو سفارش
    
    // Checkout routes
    Route::get('/checkout', [CheckoutController::class, 'index']); // اطلاعات تسویه حساب
    Route::post('/checkout', [CheckoutController::class, 'process']); // پردازش تسویه حساب
    
    // Payment routes
    Route::post('/orders/{orderId}/payments', [PaymentController::class, 'create']); // ایجاد پرداخت جدید
    Route::get('/payments', [PaymentController::class, 'history']); // تاریخچه پرداخت‌ها
    Route::get('/payments/{paymentId}', [PaymentController::class, 'show']); // جزئیات پرداخت
});

// Payment callback routes (public)
Route::get('/payments/callback/zarinpal', [PaymentController::class, 'callbackZarinpal']);
Route::get('/payments/callback/payir', [PaymentController::class, 'callbackPayir']);
Route::get('/payments/callback/idpay', [PaymentController::class, 'callbackIdpay']);

// Cart routes (available for both authenticated and guest users)
Route::get('/cart', [CartController::class, 'index']); // دریافت سبد خرید
Route::post('/cart/items', [CartController::class, 'addItem']); // افزودن محصول به سبد خرید
Route::put('/cart/items/{id}', [CartController::class, 'updateItem']); // بروزرسانی تعداد محصول
Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']); // حذف محصول از سبد خرید
Route::delete('/cart', [CartController::class, 'clearCart']); // خالی کردن سبد خرید

Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{id}', [PublicProductController::class, 'show']);


use App\Models\User;

// لاگین به عنوان ادمین
Route::get('test-login/admin', function () {
    $user = User::firstOrCreate(
        ['phone' => '09384409950'],
        ['name' => 'Admin User']
    );

    // اختصاص نقش 'admin' به کاربر
    if (!$user->hasRole('admin')) {
        $user->assignRole('admin');
    }

    $token = $user->createToken('API Token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user->load('roles') // بارگذاری نقش‌ها
    ]);
});

// لاگین به عنوان فروشنده
Route::get('/test-login/vendor', function () {
    $user = User::firstOrCreate(
        ['phone' => '09044419950'],
        ['name' => 'Vendor User']
    );

    if (!$user->hasRole('vendor')) {
        $user->assignRole('vendor');
    }

    $admin = User::firstOrCreate(
        ['phone' => '09384409950'],
        ['name' => 'Admin User']
    );

    // اختصاص نقش 'admin' به کاربر
    if (!$admin->hasRole('admin')) {
        $admin->assignRole('admin');
    }

    $vendor = \App\Models\Vendor::firstOrCreate([
        'user_id' => $user->id,
        'name' => "store name",
        'description' => "store description",
        'balance' => 0.00,
        'admin_created_by' => $admin->id, // مقدار صحیح: ID کاربر لاگین شده
    ]);

    $token = $user->createToken('API Token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user->load('roles')
    ]);
});

// لاگین به عنوان مشتری
Route::get('/test-login/user', function () {
    $user = User::firstOrCreate(
        ['phone' => '09123456789'],
        ['name' => 'User']
    );

    $token = $user->createToken('API Token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user->name
    ]);
});
