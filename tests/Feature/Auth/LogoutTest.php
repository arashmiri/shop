<?php

use App\Models\User;
use App\Services\JwtService;
use App\ValueObjects\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an authenticated user to logout', function () {
    // شماره تلفن برای تست
    $phone = '09123456789';
    $otp = '1234';

    // ارسال درخواست برای ارسال OTP
    $sendOtpResponse = $this->postJson('/api/auth/send-otp', ['phone' => $phone]);
    $sendOtpResponse->assertStatus(200);

    // دریافت توکن از پاسخ
    $token = $sendOtpResponse->json('token');

    // ذخیره OTP در دیتابیس
    \App\Models\OtpCode::where('phone', $phone)->update(['code' => $otp, 'expires_at' => now()->addMinutes(5)]);

    // تایید OTP برای دریافت توکن جدید
    $verifyResponse = $this->postJson('/api/auth/verify-otp', [
        'token' => $token,
        'code' => $otp
    ]);
    $verifyResponse->assertStatus(200);
    $authToken = $verifyResponse->json('token');  // توکن احراز هویت جدید

    // ارسال درخواست لاگ اوت همراه با توکن JWT در هدر
    $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $authToken)
        ->postJson('/api/auth/logout');

    // بررسی موفقیت‌آمیز بودن عملیات
    $logoutResponse->assertStatus(200)
        ->assertJson(['message' => 'شما خارج شدید.']);

    // بررسی اینکه هیچ توکنی برای کاربر باقی نمانده است
    $user = User::where('phone', $phone)->first();
    expect($user->tokens()->count())->toBe(0);
});

it('returns unauthorized error when user is not authenticated', function () {
    // درخواست لاگ اوت بدون ارسال توکن
    $response = $this->postJson('/api/auth/logout');

    // باید خطای 401 دریافت کند
    $response->assertStatus(401);
});
