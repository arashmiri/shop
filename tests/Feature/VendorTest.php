<?php

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // ایجاد نقش‌ها در صورت وجود نداشتن
    Role::firstOrCreate(['name' => 'vendor']);
});

it('allows admin to upgrade user to vendor', function () {
    $this->withoutExceptionHandling(); // نمایش خطاهای کامل برای دیباگ

    // ایجاد کاربر ادمین
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // احراز هویت ادمین
    Sanctum::actingAs($admin);

    // ایجاد کاربر معمولی
    $user = User::factory()->create();

    // ارسال درخواست ارتقا به فروشنده
    $response = $this->postJson('/api/admin/upgrade-to-vendor', [
        'user_id' => $user->id,
        'name' => 'Test Store',
        'description' => 'A test store description',
    ]);

    // بررسی وضعیت پاسخ
    $response->assertStatus(201);

    // بررسی اینکه فروشنده در دیتابیس ثبت شده باشد
    $this->assertDatabaseHas('vendors', [
        'user_id' => $user->id,
        'name' => 'Test Store',
        'admin_created_by' => $admin->id, // بررسی مقدار صحیح
    ]);

    // بررسی اینکه نقش فروشنده به کاربر اختصاص داده شده باشد
    expect($user->hasRole('vendor'))->toBeTrue();
});
