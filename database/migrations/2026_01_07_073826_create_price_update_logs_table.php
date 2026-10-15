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
        Schema::create('price_update_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->decimal('old_unit_price', 15, 2)->default(0);
            $table->decimal('old_box_price', 15, 2)->default(0);
            $table->decimal('old_pos_price', 15, 2)->default(0);
            
            $table->decimal('new_unit_price', 15, 2)->default(0);
            $table->decimal('new_box_price', 15, 2)->default(0);
            $table->decimal('new_pos_price', 15, 2)->default(0);
            
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_update_logs');
    }
};
