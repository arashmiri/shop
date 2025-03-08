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
        Schema::create('coupon_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable(); // سفارشی که کوپن در آن استفاده شده
            $table->decimal('discount_amount', 10, 2); // مبلغ تخفیف اعمال شده
            $table->timestamp('used_at'); // تاریخ استفاده از کوپن
            $table->timestamps();
            
            // ایجاد ایندکس‌ها و کلیدهای خارجی
            $table->unique(['coupon_id', 'user_id', 'order_id']); // هر کاربر نمی‌تواند یک کوپن را بیش از یک بار در یک سفارش استفاده کند
            $table->index('used_at');
            
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_user');
    }
};
