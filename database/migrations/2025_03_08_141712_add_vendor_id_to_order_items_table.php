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
        Schema::table('order_items', function (Blueprint $table) {
            // Add vendor_id column after product_id
            $table->unsignedBigInteger('vendor_id')->after('product_id');
            
            // Add foreign key constraint
            $table->foreign('vendor_id')
                ->references('id')->on('vendors')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['vendor_id']);
            
            // Drop column
            $table->dropColumn('vendor_id');
        });
    }
};
