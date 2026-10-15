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
        Schema::table('quotation_items', function (Blueprint $table) {
            // Remove old single-unit columns if they exist, or just modify
            // Since this is a dev env, we can just drop/add. 
            // Note: DB constraints might block dropColumn if data exists, but assuming empty/dev.
            
            // Re-designing to match: item_qty, box_qty, unit_price, box_price, total_amount
            $table->dropColumn(['unit_type', 'quantity']); // Dropping specific cols
            // unit_price exists, but we might want to rename or keep. We need box_price too.
            
            $table->integer('item_qty')->default(0)->after('product_id');
            $table->integer('box_qty')->default(0)->after('item_qty');
            // unit_price is already there
            $table->decimal('box_price', 15, 2)->default(0)->after('unit_price');
            
            // Total price exists
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
             $table->dropColumn(['item_qty', 'box_qty', 'box_price']);
             $table->enum('unit_type', ['Item', 'Box'])->default('Item');
             $table->integer('quantity')->default(0);
        });
    }
};
