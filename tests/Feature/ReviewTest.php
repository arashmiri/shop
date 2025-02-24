<?php

use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // ایجاد یک کاربر و احراز هویت آن
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);

    // ایجاد یک محصول
    $this->product = Product::factory()->create();
});

test('user can add review to product', function () {
    $this->withoutExceptionHandling();

    // ارسال درخواست POST برای افزودن نظر
    $response = $this->postJson("/api/products/{$this->product->id}/reviews", [
        'rating' => 5,
        'comment' => 'Great product!',
    ]);

    // بررسی پاسخ موفقیت‌آمیز
    $response->assertStatus(201)
        ->assertJsonPath('review.rating', 5)  // اصلاح مسیر کلید برای بررسی
        ->assertJsonPath('review.comment', 'Great product!');

    // بررسی ذخیره شدن نظر در دیتابیس
    $this->assertDatabaseHas('reviews', [
        'product_id' => $this->product->id,
        'user_id' => $this->user->id,
        'rating' => 5,
        'comment' => 'Great product!',
    ]);
});

test('user can get reviews for a product', function () {
    // افزودن یک نظر به محصول
    $this->postJson("/api/products/{$this->product->id}/reviews", [
        'rating' => 5,
        'comment' => 'Great product!',
    ]);

    // دریافت لیست نظرات محصول
    $response = $this->getJson("/api/products/{$this->product->id}/reviews");

    // بررسی پاسخ موفقیت‌آمیز و تعداد نظرات
    $response->assertStatus(200)
        ->assertJsonCount(1) // بررسی تعداد نظرات
        ->assertJsonPath('0.rating', 5) // بررسی صحیح بودن نظر اول
        ->assertJsonPath('0.comment', 'Great product!');
});


