<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // کد کوپن (منحصر به فرد)
            $table->string('name'); // نام کوپن
            $table->text('description')->nullable(); // توضیحات کوپن
            $table->enum('type', ['percentage', 'fixed']); // نوع کوپن: درصدی یا مبلغ ثابت
            $table->decimal('value', 10, 2); // مقدار کوپن (درصد یا مبلغ)
            $table->decimal('min_order_amount', 10, 2)->nullable(); // حداقل مبلغ سفارش برای استفاده از کوپن
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // حداکثر مبلغ تخفیف (برای کوپن‌های درصدی)
            $table->unsignedBigInteger('vendor_id')->nullable(); // فروشنده مرتبط (اگر کوپن مخصوص یک فروشنده باشد)
            $table->unsignedBigInteger('product_id')->nullable(); // محصول مرتبط (اگر کوپن مخصوص یک محصول باشد)
            $table->unsignedBigInteger('category_id')->nullable(); // دسته‌بندی مرتبط (اگر کوپن مخصوص یک دسته‌بندی باشد)
            $table->unsignedInteger('usage_limit')->nullable(); // محدودیت تعداد استفاده از کوپن
            $table->unsignedInteger('usage_limit_per_user')->nullable(); // محدودیت تعداد استفاده از کوپن برای هر کاربر
            $table->unsignedInteger('used_count')->default(0); // تعداد دفعات استفاده شده
            $table->boolean('is_active')->default(true); // وضعیت فعال بودن کوپن
            $table->timestamp('starts_at')->nullable(); // تاریخ شروع اعتبار کوپن
            $table->timestamp('expires_at')->nullable(); // تاریخ پایان اعتبار کوپن
            $table->timestamps();
            
            // ایجاد ایندکس‌ها
            $table->index('code');
            $table->index('vendor_id');
            $table->index('product_id');
            $table->index('category_id');
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
            
            // ایجاد کلیدهای خارجی
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // توجه: فرض می‌کنیم جدول categories وجود دارد، اگر وجود ندارد این خط را حذف کنید
            // $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
