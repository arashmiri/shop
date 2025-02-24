<?php

use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // ایجاد نقش‌ها در صورت وجود نداشتن
    Role::firstOrCreate(['name' => 'vendor']);

    // ایجاد یک کاربر و تبدیل آن به فروشنده
    $this->vendor = User::factory()->create();
    $this->vendor->assignRole('vendor');

    // ایجاد کاربر مدیر (برای admin_created_by)
    $this->admin = User::factory()->create();

    // ایجاد فروشگاه برای این کاربر
    $this->vendor->vendor()->create([
        'name' => 'Test Vendor',
        'user_id' => $this->vendor->id,
        'admin_created_by' => $this->admin->id, // استفاده از شناسه مدیر
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

test('a vendor can update a product', function () {
    $product = Product::factory()->create(['vendor_id' => $this->vendor->vendor->id]);

    $response = $this->putJson("/api/vendor/products/{$product->id}", [
        'name' => 'Updated Product',
        'description' => 'Updated description',
        'price' => 1500,
        'stock' => 20,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('product.name', 'Updated Product')
        ->assertJsonPath('product.price', 1500)
        ->assertJsonPath('product.stock', 20);
});

test('a vendor can delete a product', function () {
    $product = Product::factory()->create(['vendor_id' => $this->vendor->vendor->id]);

    $response = $this->deleteJson("/api/vendor/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'محصول با موفقیت حذف شد']);
});


