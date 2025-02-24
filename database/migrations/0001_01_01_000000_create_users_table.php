<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * اجرای migration.
     */
    public function up(): void
    {
        // ایجاد جدول users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique(); // شماره موبایل به عنوان شناسه اصلی
            $table->string('name')->nullable(); // نام کاربر (اختیاری)
            $table->rememberToken();
            $table->timestamps();
        });

        // ایجاد جدول password_reset_tokens با استفاده از شماره موبایل به عنوان کلید اصلی
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('phone')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // ایجاد جدول sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * برگرداندن تغییرات migration.
     */
    public function down(): void
    {
        // ابتدا جداول وابسته حذف شوند تا مشکلی در حذف users پیش نیاید
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
