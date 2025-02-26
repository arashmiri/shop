<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // ایجاد نقش 'vendor' در صورت عدم وجود
    Role::firstOrCreate(['name' => 'vendor']);

    // ایجاد یک کاربر ادمین و ذخیره ID آن
    $admin = User::factory()->create();
    $this->adminId = $admin->id;

    // ایجاد یک کاربر و تبدیل آن به فروشنده
    $this->vendor = User::factory()->create();
    $this->vendor->assignRole('vendor');

    // ایجاد فروشگاه برای این کاربر
    $this->vendor->vendor()->create([
        'name' => 'Test Vendor',
        'user_id' => $this->vendor->id,
        'admin_created_by' => $this->adminId, // استفاده از id ادمین واقعی
    ]);

    $this->vendor->refresh();
    Sanctum::actingAs($this->vendor);

    $this->products = Product::factory()->count(3)->create([
        'vendor_id' => $this->vendor->vendor->id,
    ]);

    $this->product = $this->products->first();
});


it('shows products with vendors', function () {
    $this->withoutExceptionHandling();

    // ارسال درخواست GET به API برای دریافت محصولات
    $response = $this->getJson('/api/products');

    // بررسی وضعیت پاسخ
    $response->assertStatus(200);

    // بررسی اینکه تعداد محصولات دقیقا ۳ باشد
    $response->assertJsonCount(3, 'data');

    // بررسی اینکه هر محصول شامل اطلاعات فروشنده باشد
    foreach ($response->json('data') as $product) {
        $this->assertArrayHasKey('vendor', $product);
        $this->assertNotNull($product['vendor']);
    }
});

it('shows a single product with vendor', function () {
    $this->withoutExceptionHandling();
    $response = $this->getJson("/api/products/{$this->product->id}");

    // بررسی وضعیت پاسخ
    $response->assertStatus(200);

    // بررسی اینکه داده‌های محصول شامل اطلاعات فروشنده باشند
    $response->assertJsonFragment([
        'id' => $this->product->id,
        'vendor_id' => $this->product->vendor_id,
        'name' => $this->product->name,
    ]);

    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'description',
            'price',
            'stock',
            'vendor' => [
                'id',
                'name',
                'description',
            ]
        ]
    ]);
});
