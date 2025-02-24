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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->string('transaction_id')->unique();
            $table->string('gateway'); // نام درگاه پرداخت (مثلاً zarinpal, paypal)
            $table->timestamp('paid_at')->nullable(); // زمان نهایی شدن پرداخت
            $table->json('details')->nullable(); // ذخیره اطلاعات اضافی مثل شماره کارت و ref_id
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
