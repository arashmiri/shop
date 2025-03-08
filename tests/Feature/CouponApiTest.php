<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
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

// تست دریافت لیست کوپن‌های فروشنده
test('vendor can view their coupons', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد کوپن‌ها
    Coupon::create([
        'code' => 'TEST10',
        'name' => 'کوپن تست 10 درصد',
        'description' => 'توضیحات کوپن تست',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
        'used_count' => 0,
    ]);
    
    // ارسال درخواست به API
    $response = $this->getJson('/api/vendor/coupons');
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'TEST10');
});

// تست ایجاد کوپن جدید توسط فروشنده
test('vendor can create a new coupon', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // داده‌های کوپن جدید
    $couponData = [
        'code' => 'SUMMER30',
        'name' => 'کوپن تابستانه',
        'description' => 'تخفیف ویژه تابستان',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 30,
        'min_order_amount' => 200000,
        'max_discount_amount' => 100000,
        'product_id' => $this->product->id,
        'usage_limit' => 100,
        'usage_limit_per_user' => 1,
        'is_active' => true,
        'starts_at' => now()->format('Y-m-d H:i:s'),
        'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
    ];
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/vendor/coupons', $couponData);
    
    // بررسی پاسخ
    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.code', 'SUMMER30')
        ->assertJsonPath('data.name', 'کوپن تابستانه')
        ->assertJsonPath('data.type', Coupon::TYPE_PERCENTAGE);
    
    // بررسی مقدار value با استفاده از assertEquals
    $this->assertEquals(30, (float)$response->json('data.value'));
    $this->assertEquals($this->product->id, $response->json('data.product_id'));
    
    // بررسی ذخیره شدن در دیتابیس
    $this->assertDatabaseHas('coupons', [
        'code' => 'SUMMER30',
        'name' => 'کوپن تابستانه',
        'vendor_id' => $this->vendor->id,
        'product_id' => $this->product->id,
    ]);
});

// تست ایجاد کوپن با کد تصادفی
test('vendor can create a coupon with auto-generated code', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // داده‌های کوپن جدید بدون کد
    $couponData = [
        'name' => 'کوپن با کد تصادفی',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 15,
        'is_active' => true,
    ];
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/vendor/coupons', $couponData);
    
    // بررسی پاسخ
    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'کوپن با کد تصادفی');
    
    // بررسی وجود کد تصادفی
    $this->assertNotNull($response->json('data.code'));
    $this->assertIsString($response->json('data.code'));
    $this->assertGreaterThanOrEqual(8, strlen($response->json('data.code')));
});

// تست عدم امکان ایجاد کوپن با کد تکراری
test('vendor cannot create a coupon with duplicate code', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد کوپن اولیه
    Coupon::create([
        'code' => 'UNIQUE10',
        'name' => 'کوپن اولیه',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
        'used_count' => 0,
    ]);
    
    // داده‌های کوپن جدید با کد تکراری
    $couponData = [
        'code' => 'UNIQUE10',
        'name' => 'کوپن دوم',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 20,
        'is_active' => true,
    ];
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/vendor/coupons', $couponData);
    
    // بررسی پاسخ
    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'کد کوپن تکراری است.');
});

// تست عدم دسترسی کاربر عادی به ایجاد کوپن
test('normal user cannot create a coupon', function () {
    // احراز هویت به عنوان کاربر عادی
    Sanctum::actingAs($this->user, ['*']);
    
    // داده‌های کوپن جدید
    $couponData = [
        'code' => 'USER10',
        'name' => 'کوپن کاربر',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 10,
    ];
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/vendor/coupons', $couponData);
    
    // بررسی پاسخ
    $response->assertStatus(403);
});

// تست مشاهده جزئیات کوپن توسط فروشنده
test('vendor can view coupon details', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد کوپن
    $coupon = Coupon::create([
        'code' => 'DETAIL10',
        'name' => 'کوپن جزئیات',
        'description' => 'توضیحات کوپن جزئیات',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
        'used_count' => 0,
    ]);
    
    // ارسال درخواست به API
    $response = $this->getJson("/api/vendor/coupons/{$coupon->id}");
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $coupon->id)
        ->assertJsonPath('data.code', 'DETAIL10')
        ->assertJsonPath('data.name', 'کوپن جزئیات');
});

// تست ویرایش کوپن توسط فروشنده
test('vendor can update their coupon', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد کوپن
    $coupon = Coupon::create([
        'code' => 'UPDATE10',
        'name' => 'کوپن قدیمی',
        'description' => 'توضیحات کوپن قدیمی',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
        'used_count' => 0,
    ]);
    
    // داده‌های به‌روزرسانی
    $updateData = [
        'code' => 'UPDATE20',
        'name' => 'کوپن به‌روزرسانی شده',
        'description' => 'توضیحات جدید',
        'value' => 20,
        'is_active' => false,
    ];
    
    // ارسال درخواست به API
    $response = $this->putJson("/api/vendor/coupons/{$coupon->id}", $updateData);
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.code', 'UPDATE20')
        ->assertJsonPath('data.name', 'کوپن به‌روزرسانی شده');
    
    // بررسی مقدار value با استفاده از assertEquals
    $this->assertEquals(20, (float)$response->json('data.value'));
    $this->assertFalse((bool)$response->json('data.is_active'));
    
    // بررسی به‌روزرسانی در دیتابیس
    $this->assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'code' => 'UPDATE20',
        'name' => 'کوپن به‌روزرسانی شده',
        'value' => 20,
        'is_active' => 0,
    ]);
});

// تست حذف کوپن توسط فروشنده
test('vendor can delete their coupon', function () {
    // احراز هویت به عنوان فروشنده
    Sanctum::actingAs($this->vendorUser, ['*']);
    
    // ایجاد کوپن
    $coupon = Coupon::create([
        'code' => 'DELETE10',
        'name' => 'کوپن برای حذف',
        'description' => 'توضیحات کوپن برای حذف',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 10,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
        'used_count' => 0,
    ]);
    
    // ارسال درخواست به API
    $response = $this->deleteJson("/api/vendor/coupons/{$coupon->id}");
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'کوپن با موفقیت حذف شد.');
    
    // بررسی حذف از دیتابیس
    $this->assertDatabaseMissing('coupons', [
        'id' => $coupon->id,
    ]);
});

// تست اعمال کوپن به سبد خرید
test('user can apply coupon to cart', function () {
    // احراز هویت به عنوان کاربر عادی
    Sanctum::actingAs($this->user, ['*']);
    
    // ایجاد کوپن
    $coupon = Coupon::create([
        'code' => 'CART20',
        'name' => 'کوپن سبد خرید',
        'description' => 'توضیحات کوپن سبد خرید',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 20,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
        'used_count' => 0,
    ]);
    
    // ایجاد سبد خرید
    $cart = Cart::create([
        'user_id' => $this->user->id,
    ]);
    
    // افزودن محصول به سبد خرید
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
    ]);
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/coupons/apply', [
        'code' => 'CART20',
    ]);
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'کوپن با موفقیت اعمال شد.');
    
    // بررسی اعمال کوپن به سبد خرید
    $this->assertDatabaseHas('carts', [
        'id' => $cart->id,
        'coupon_id' => $coupon->id,
    ]);
    
    // بررسی مقدار تخفیف
    $this->assertEquals(40000, $response->json('data.discount_amount')); // 20% of 200000 (2 * 100000)
    $this->assertEquals(160000, $response->json('data.total')); // 200000 - 40000
});

// تست حذف کوپن از سبد خرید
test('user can remove coupon from cart', function () {
    // احراز هویت به عنوان کاربر عادی
    Sanctum::actingAs($this->user, ['*']);
    
    // ایجاد کوپن
    $coupon = Coupon::create([
        'code' => 'REMOVE20',
        'name' => 'کوپن برای حذف',
        'description' => 'توضیحات کوپن برای حذف',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 20,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
        'used_count' => 0,
    ]);
    
    // ایجاد سبد خرید با کوپن
    $cart = Cart::create([
        'user_id' => $this->user->id,
        'coupon_id' => $coupon->id,
    ]);
    
    // افزودن محصول به سبد خرید
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
    ]);
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/coupons/remove');
    
    // بررسی پاسخ
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'کوپن با موفقیت حذف شد.');
    
    // بررسی حذف کوپن از سبد خرید
    $this->assertDatabaseHas('carts', [
        'id' => $cart->id,
        'coupon_id' => null,
    ]);
    
    // بررسی قیمت کل بدون تخفیف
    $this->assertEquals(100000, $response->json('data.total'));
});

// تست اعمال کوپن به سبد خرید خالی
test('cannot apply coupon to empty cart', function () {
    // احراز هویت به عنوان کاربر عادی
    Sanctum::actingAs($this->user, ['*']);
    
    // ایجاد کوپن
    Coupon::create([
        'code' => 'EMPTY20',
        'name' => 'کوپن سبد خالی',
        'description' => 'توضیحات کوپن سبد خالی',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 20,
        'vendor_id' => $this->vendor->id,
        'is_active' => true,
        'used_count' => 0,
    ]);
    
    // ایجاد سبد خرید خالی
    Cart::create([
        'user_id' => $this->user->id,
    ]);
    
    // ارسال درخواست به API
    $response = $this->postJson('/api/coupons/apply', [
        'code' => 'EMPTY20',
    ]);
    
    // بررسی پاسخ
    $response->assertStatus(400)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'سبد خرید شما خالی است.');
});

// تست اعمال کوپن نامعتبر
test('cannot apply invalid coupon', function () {
    // احراز هویت به عنوان کاربر عادی
    Sanctum::actingAs($this->user, ['*']);
    
    // ایجاد سبد خرید
    $cart = Cart::create([
        'user_id' => $this->user->id,
    ]);
    
    // افزودن محصول به سبد خرید
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
    ]);
    
    // ارسال درخواست به API با کد نامعتبر
    $response = $this->postJson('/api/coupons/apply', [
        'code' => 'INVALID',
    ]);
    
    // بررسی پاسخ
    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'کد کوپن نامعتبر است.');
});
