<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows an authenticated user to logout', function () {
    // ایجاد کاربر و احراز هویت با Sanctum
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // درخواست لاگ اوت
    $response = $this->postJson('/api/auth/logout');

    // بررسی موفقیت‌آمیز بودن عملیات
    $response->assertStatus(200)
        ->assertJson(['message' => 'شما خارج شدید.']);

    // بررسی اینکه هیچ توکنی برای کاربر باقی نمانده است
    expect($user->tokens()->count())->toBe(0);
});

it('returns unauthorized error when user is not authenticated', function () {
    // درخواست لاگ اوت بدون احراز هویت
    $response = $this->postJson('/api/auth/logout');

    // باید خطای 401 دریافت کند
    $response->assertStatus(401);
});
