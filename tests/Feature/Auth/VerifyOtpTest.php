<?php

use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

it('can verify OTP successfully', function () {
    $phone = '09384409950';
    $otp = '1234';

    // ایجاد OTP در دیتابیس
    OtpCode::create([
        'phone' => $phone,
        'code' => $otp,
        'expires_at' => now()->addMinutes(5), // معتبر
    ]);

    Session::put('phone', $phone);

    // بررسی اینکه OTP قبل از ارسال درخواست در دیتابیس موجود است
    $this->assertDatabaseHas('otp_codes', ['phone' => $phone, 'code' => $otp]);

    // ارسال درخواست برای تأیید OTP
    $response = $this->postJson('/api/auth/verify-otp', [
        'code' => $otp
    ]);

    // بررسی اینکه توکن برگردانده شده است
    $response->assertStatus(200)
        ->assertJsonStructure(['token']);

    // بررسی اینکه OTP از دیتابیس حذف شده است
    $this->assertDatabaseMissing('otp_codes', ['phone' => $phone, 'code' => $otp]);
});


it('fails when OTP is expired', function () {
    $phone = '09384409950';

    // ایجاد یک OTP منقضی شده
    OtpCode::create([
        'phone' => $phone,
        'code' => '1234',
        'expires_at' => now()->subMinute(), // منقضی شده
    ]);

    Session::put('phone', $phone);

    // ارسال درخواست برای تأیید OTP
    $response = $this->postJson('/api/auth/verify-otp', [
        'code' => '1234'
    ]);

    $response->assertStatus(422)
        ->assertJson(['message' => 'کد تأیید منقضی شده است.']);
});

it('fails when OTP is invalid', function () {
    $phone = '09384409950';

    // ایجاد یک OTP معتبر اما کد اشتباه در درخواست
    OtpCode::create([
        'phone' => $phone,
        'code' => '5678', // کد درست این است ولی ما کد اشتباه ارسال می‌کنیم
        'expires_at' => now()->addMinutes(5),
    ]);

    Session::put('phone', $phone);

    // ارسال درخواست برای تأیید OTP با کد اشتباه
    $response = $this->postJson('/api/auth/verify-otp', [
        'code' => '1234' // کد اشتباه
    ]);

    $response->assertStatus(422)
        ->assertJson(['message' => 'کد تأیید نامعتبر است.']);
});

it('fails when phone number is missing from session', function () {
    // بدون قرار دادن شماره موبایل در سشن
    $response = $this->postJson('/api/auth/verify-otp', [
        'code' => '1234'
    ]);

    $response->assertStatus(422)
        ->assertJson(['message' => 'فرمت شماره موبایل یا کد تأیید نامعتبر است.']);
});
