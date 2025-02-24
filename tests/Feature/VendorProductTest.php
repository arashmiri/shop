<?php

use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // ایجاد یک کاربر و تبدیل آن به فروشنده
    $this->vendor = User::factory()->create();
    $this->vendor->assignRole('vendor'); // نقش فروشنده را تنظیم کنید

    // ایجاد فروشگاه برای این کاربر
    $this->vendor->vendor()->create([
        'name' => 'Test Vendor',
        'user_id' => $this->vendor->id,
        'admin_created_by' => 1, // اطمینان حاصل کنید که به درستی به یک مدیر مرتبط باشد
    ]);

    Sanctum::actingAs($this->vendor); // احراز هویت فروشنده
});



test('a vendor can create a product', function () {
    $this->withoutExceptionHandling();

    $response = $this->postJson('/api/vendor/products', [
        'name' => 'Test Product',
        'description' => 'This is a test product',
        'price' => 1000,
        'stock' => 10,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('product.name', 'Test Product');
});

test('a vendor can see their products', function () {
    Product::factory()->count(3)->create(['vendor_id' => $this->vendor->vendor->id]);

    $response = $this->getJson('/api/vendor/products');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

