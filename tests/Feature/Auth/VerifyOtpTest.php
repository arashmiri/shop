<?php

use App\Models\OtpCode;
use App\Models\User;
use App\Services\JwtService;
use App\ValueObjects\PhoneNumber;
use App\ValueObjects\OtpCode as OtpCodeObject;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('verifies OTP successfully and returns auth token', function () {
    $this->withoutExceptionHandling();

    $phone = new PhoneNumber('09123456789');
    $otp = '1234';

    // ارسال OTP و دریافت توکن از پاسخ API
    $sendOtpResponse = $this->postJson('/api/auth/send-otp', ['phone' => $phone->getPhone()]);
    $sendOtpResponse->assertStatus(200);

    $token = $sendOtpResponse->json('token');

    // بروزرسانی کد OTP در دیتابیس
    OtpCode::where('phone', $phone->getPhone())
        ->update(['code' => $otp, 'expires_at' => now()->addMinutes(5)]);

    // ارسال درخواست تأیید OTP
    $response = $this->postJson('/api/auth/verify-otp', [
        'token' => $token,
        'code' => $otp
    ]);

    // بررسی موفقیت تأیید و دریافت توکن احراز هویت
    $response->assertStatus(200)->assertJsonStructure(['token']);
});

it('fails when OTP is incorrect', function () {
    $phone = new PhoneNumber('09123456789');
    $otp = '1234';

    // ایجاد OTP در دیتابیس
    OtpCode::create(['phone' => $phone->getPhone(), 'code' => $otp, 'expires_at' => now()->addMinutes(5)]);

    // ایجاد توکن JWT
    $jwtService = app(JwtService::class);
    $token = $jwtService->generateToken($phone->getPhone());

    // ارسال OTP اشتباه
    $response = $this->postJson('/api/auth/verify-otp', [
        'token' => $token,
        'code' => '0000' // کد نادرست
    ]);

    $response->assertStatus(422)
        ->assertJson(['message' => 'کد تأیید نامعتبر است.']);
});

it('fails when OTP is expired', function () {
    $phone = new PhoneNumber('09123456789');
    $otp = '1234';

    // ایجاد OTP منقضی شده در دیتابیس
    OtpCode::create(['phone' => $phone->getPhone(), 'code' => $otp, 'expires_at' => now()->subMinutes(1)]);

    // ایجاد توکن JWT
    $jwtService = app(JwtService::class);
    $token = $jwtService->generateToken($phone->getPhone());

    // ارسال درخواست تأیید
    $response = $this->postJson('/api/auth/verify-otp', [
        'token' => $token,
        'code' => $otp
    ]);

    $response->assertStatus(422)
        ->assertJson(['message' => 'کد تأیید منقضی شده است.']);
});

it('fails when token is invalid', function () {
    $response = $this->postJson('/api/auth/verify-otp', [
        'token' => 'invalid-token',
        'code' => '1234'
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'توکن نامعتبر یا منقضی شده است.']);
});
