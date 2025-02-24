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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // کاربری که نظر داده
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // محصولی که برای آن نظر داده شده
            $table->tinyInteger('rating')->unsigned(); // امتیاز (رأی)
            $table->text('comment')->nullable(); // متن نظر
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
