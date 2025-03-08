<?php

use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\RoleSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    // اجرای seeder نقش‌ها
    $this->seed(RoleSeeder::class);
    
    // ایجاد کاربر ادمین
    $this->admin = User::factory()->create(['phone' => '09384409950']);
    $this->admin->assignRole('admin');
    
    // ایجاد کاربر فروشنده
    $this->vendorUser = User::factory()->create(['phone' => '09044419950']);
    $this->vendorUser->assignRole('vendor');
    
    // ایجاد فروشنده
    $this->vendor = Vendor::create([
        'user_id' => $this->vendorUser->id,
        'name' => 'فروشگاه تست',
        'description' => 'توضیحات فروشگاه تست',
        'balance' => 0.00,
        'admin_created_by' => $this->admin->id,
    ]);
    
    // ایجاد کاربر عادی
    $this->user = User::factory()->create(['phone' => '09123456789']);
    
    // ایجاد محصول
    $this->product = Product::create([
        'vendor_id' => $this->vendor->id,
        'name' => 'محصول تست',
        'description' => 'توضیحات محصول تست',
        'price' => 100000,
        'stock' => 10,
    ]);
});

// تست دریافت لیست تخفیف‌های فعال
test('can view active discounts', function () {
    // ایجاد چند تخفیف
    Discount::create([
        'name' => 'تخفیف فعال',
        'description' => 'توضیحات تخفیف فعال',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
    ]);
    
    Discount::create([
        'name' => 'تخفیف غیرفعال',
        'description' => 'توضیحات تخفیف غیرفعال',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 15,
        'vendor_id' => $this->vendor->id,
        'is_active' => false,
    ]);
    
    // ارسال درخواست به API
    $response = $this->getJson('/api/discounts');
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'تخفیف فعال');
});

// تست دریافت تخفیف‌های مربوط به یک محصول
test('can view discounts for a specific product', function () {
    // ایجاد تخفیف برای محصول
    Discount::create([
        'name' => 'تخفیف محصول',
        'description' => 'توضیحات تخفیف محصول',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 20,
        'vendor_id' => $this->vendor->id,
        'product_id' => $this->product->id,
        'is_active' => true,
    ]);
    
    // ایجاد تخفیف برای فروشنده
    Discount::create([
        'name' => 'تخفیف فروشنده',
        'description' => 'توضیحات تخفیف فروشنده',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
    ]);
    
    // ارسال درخواست به API
    $response = $this->getJson("/api/products/{$this->product->id}/discounts");
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'تخفیف محصول')
        ->assertJsonPath('data.1.name', 'تخفیف فروشنده');
});

// تست دریافت لیست تخفیف‌های فروشنده
test('vendor can view their discounts', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد تخفیف‌ها
    Discount::create([
        'name' => 'تخفیف 1',
        'description' => 'توضیحات تخفیف 1',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
    ]);
    
    // ارسال درخواست به API
    $response = $this->getJson('/api/vendor/discounts');
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data');
});

// تست ایجاد تخفیف جدید توسط فروشنده
test('vendor can create a new discount', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // داده‌های تخفیف جدید
    $discountData = [
        'name' => 'تخفیف جدید',
        'description' => 'توضیحات تخفیف جدید',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 15,
        'min_order_amount' => 50000,
        'max_discount_amount' => 30000,
        'product_id' => $this->product->id,
        'is_active' => true,
        'starts_at' => now()->format('Y-m-d H:i:s'),
        'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
    ];
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/vendor/discounts', $discountData);
    
    // بررسی پاسخ
    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'تخفیف جدید')
        ->assertJsonPath('data.type', Discount::TYPE_PERCENTAGE);
    
    // بررسی مقدار value با استفاده از assertEquals
    $this->assertEquals(15, (float)$response->json('data.value'));
    $this->assertEquals($this->product->id, $response->json('data.product_id'));
    
    // بررسی ذخیره شدن در دیتابیس
    $this->assertDatabaseHas('discounts', [
        'name' => 'تخفیف جدید',
        'vendor_id' => $this->vendor->id,
        'product_id' => $this->product->id,
    ]);
});

// تست عدم دسترسی کاربر عادی به ایجاد تخفیف
test('normal user cannot create a discount', function () {
    // احراز هویت به عنوان کاربر عادی
    Sanctum::actingAs($this->user, ['*']);
    
    // داده‌های تخفیف جدید
    $discountData = [
        'name' => 'تخفیف جدید',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 15,
    ];
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/vendor/discounts', $discountData);
    
    // بررسی پاسخ
    $response->assertStatus(403);
});

// تست مشاهده جزئیات تخفیف توسط فروشنده
test('vendor can view discount details', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد تخفیف
    $discount = Discount::create([
        'name' => 'تخفیف تست',
        'description' => 'توضیحات تخفیف تست',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
    ]);
    
    // ارسال درخواست به API
    $response = $this->getJson("/api/vendor/discounts/{$discount->id}");
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $discount->id)
        ->assertJsonPath('data.name', 'تخفیف تست');
});

// تست ویرایش تخفیف توسط فروشنده
test('vendor can update their discount', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد تخفیف
    $discount = Discount::create([
        'name' => 'تخفیف قدیمی',
        'description' => 'توضیحات تخفیف قدیمی',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
    ]);
    
    // داده‌های به‌روزرسانی
    $updateData = [
        'name' => 'تخفیف به‌روزرسانی شده',
        'description' => 'توضیحات جدید',
        'value' => 20,
        'is_active' => false,
    ];
    
    // ارسال درخواست به API
    $response = $this->putJson("/api/vendor/discounts/{$discount->id}", $updateData);
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'تخفیف به‌روزرسانی شده');
    
    // بررسی مقدار value با استفاده از assertEquals
    $this->assertEquals(20, (float)$response->json('data.value'));
    $this->assertFalse((bool)$response->json('data.is_active'));
    
    // بررسی به‌روزرسانی در دیتابیس
    $this->assertDatabaseHas('discounts', [
        'id' => $discount->id,
        'name' => 'تخفیف به‌روزرسانی شده',
        'value' => 20,
        'is_active' => 0,
    ]);
});

// تست حذف تخفیف توسط فروشنده
test('vendor can delete their discount', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد تخفیف
    $discount = Discount::create([
        'name' => 'تخفیف برای حذف',
        'description' => 'توضیحات تخفیف برای حذف',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
    ]);
    
    // ارسال درخواست به API
    $response = $this->deleteJson("/api/vendor/discounts/{$discount->id}");
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'تخفیف با موفقیت حذف شد.');
    
    // بررسی حذف از دیتابیس
    $this->assertDatabaseMissing('discounts', [
        'id' => $discount->id,
    ]);
});

// تست عدم دسترسی فروشنده به تخفیف‌های فروشنده دیگر
test('vendor cannot access another vendors discount', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد فروشنده دیگر
    $otherVendorUser = User::factory()->create(['phone' => '09111111111']);
    $otherVendorUser->assignRole('vendor');
    
    $otherVendor = Vendor::create([
        'user_id' => $otherVendorUser->id,
        'name' => 'فروشگاه دیگر',
        'description' => 'توضیحات فروشگاه دیگر',
        'balance' => 0.00,
        'admin_created_by' => $this->admin->id,
    ]);
    
    // ایجاد تخفیف برای فروشنده دیگر
    $otherDiscount = Discount::create([
        'name' => 'تخفیف فروشنده دیگر',
        'description' => 'توضیحات تخفیف فروشنده دیگر',
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $otherVendor->id,
        'is_active' => true,
    ]);
    
    // ارسال درخواست به API
    $response = $this->getJson("/api/vendor/discounts/{$otherDiscount->id}");
    
    // بررسی پاسخ
    $response->assertStatus(403);
    
    // تلاش برای ویرایش
    $response = $this->putJson("/api/vendor/discounts/{$otherDiscount->id}", [
        'name' => 'تخفیف تغییر یافته',
    ]);
    
    // بررسی پاسخ
    $response->assertStatus(403);
    
    // تلاش برای حذف
    $response = $this->deleteJson("/api/vendor/discounts/{$otherDiscount->id}");
    
    // بررسی پاسخ
    $response->assertStatus(403);
});
