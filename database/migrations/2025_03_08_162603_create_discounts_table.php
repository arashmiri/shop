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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام تخفیف
            $table->text('description')->nullable(); // توضیحات تخفیف
            $table->enum('type', ['percentage', 'fixed']); // نوع تخفیف: درصدی یا مبلغ ثابت
            $table->decimal('value', 10, 2); // مقدار تخفیف (درصد یا مبلغ)
            $table->decimal('min_order_amount', 10, 2)->nullable(); // حداقل مبلغ سفارش برای اعمال تخفیف
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // حداکثر مبلغ تخفیف (برای تخفیف‌های درصدی)
            $table->unsignedBigInteger('vendor_id')->nullable(); // فروشنده مرتبط (اگر تخفیف مخصوص یک فروشنده باشد)
            $table->unsignedBigInteger('product_id')->nullable(); // محصول مرتبط (اگر تخفیف مخصوص یک محصول باشد)
            $table->unsignedBigInteger('category_id')->nullable(); // دسته‌بندی مرتبط (اگر تخفیف مخصوص یک دسته‌بندی باشد)
            $table->boolean('is_active')->default(true); // وضعیت فعال بودن تخفیف
            $table->timestamp('starts_at')->nullable(); // تاریخ شروع تخفیف
            $table->timestamp('expires_at')->nullable(); // تاریخ پایان تخفیف
            $table->timestamps();
            
            // ایجاد ایندکس‌ها
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
        Schema::dropIfExists('discounts');
    }
};
