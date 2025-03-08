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
        Schema::table('orders', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['vendor_id']);
            
            // Then drop the column
            $table->dropColumn('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add the column back
            $table->unsignedBigInteger('vendor_id')->after('user_id');
            
            // Add the foreign key constraint back
            $table->foreign('vendor_id')
                ->references('id')->on('vendors')
                ->onDelete('cascade');
        });
    }
};
