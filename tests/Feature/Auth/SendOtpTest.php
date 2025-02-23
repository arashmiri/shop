<?php

use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;


uses(RefreshDatabase::class);

it('can send OTP successfully', function () {
    $this->withoutExceptionHandling();

    $phone = '09384409950';

    // ارسال درخواست به API
    $response = $this->postJson('/api/auth/send-otp', ['phone' => $phone]);

    // بررسی موفقیت ارسال
    $response->assertStatus(200)
        ->assertJson(['message' => 'کد تأیید ارسال شد.']);

    // بررسی اینکه OTP در دیتابیس ذخیره شده است
    $this->assertDatabaseHas('otp_codes', ['phone' => $phone]);

    // بررسی اینکه شماره موبایل در سشن ذخیره شده است
    expect(Session::get('phone'))->toBe($phone);
});

it('fails when sending OTP with invalid phone', function () {
    $response = $this->postJson('/api/auth/send-otp', ['phone' => '123']);
    $response->assertStatus(422);
});

it('replaces old OTP with new one when resending', function () {
    $phone = '09044419950';

    // ارسال اولین OTP
    $this->postJson('/api/auth/send-otp', ['phone' => $phone]);

    // ذخیره اولین OTP
    $firstOtp = OtpCode::where('phone', $phone)->first()->code;

    OtpCode::where('phone', $phone)->update(['expires_at' => now()->subSeconds(121)]);

    // ارسال دوباره OTP
    $this->postJson('/api/auth/send-otp', ['phone' => $phone]);

    // ذخیره OTP جدید
    $secondOtp = OtpCode::where('phone', $phone)->first()->code;

    // بررسی که OTP جدید با قبلی فرق داشته باشد
    expect($firstOtp)->not->toBe($secondOtp);
});

it('prevents sending OTP again within 2 minutes', function () {
    $phone = '09123456789';

    // ارسال اولیه OTP
    $this->postJson('/api/auth/send-otp', ['phone' => $phone])
        ->assertStatus(200)
        ->assertJson(['message' => 'کد تأیید ارسال شد.']);

    // تلاش برای ارسال مجدد قبل از ۲ دقیقه
    $this->postJson('/api/auth/send-otp', ['phone' => $phone])
        ->assertStatus(429)
        ->assertJson(['message' => 'لطفاً دو دقیقه صبر کنید و سپس دوباره درخواست دهید.']);

    // انتظار اینکه فقط یک رکورد در جدول OTP ذخیره شده باشد
    expect(OtpCode::where('phone', $phone)->count())->toBe(1);
});

it('allows sending OTP after 2 minutes', function () {
    $phone = '09123456789';

    // ارسال اولیه OTP
    $this->postJson('/api/auth/send-otp', ['phone' => $phone])
        ->assertStatus(200);

    // تنظیم زمان در آینده (بیش از 2 دقیقه)
    OtpCode::where('phone', $phone)->update(['expires_at' => now()->subSeconds(121)]);

    // تلاش برای ارسال مجدد بعد از ۲ دقیقه
    $this->postJson('/api/auth/send-otp', ['phone' => $phone])
        ->assertStatus(200)
        ->assertJson(['message' => 'کد تأیید ارسال شد.']);
});
