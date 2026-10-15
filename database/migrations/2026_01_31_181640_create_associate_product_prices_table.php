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
        Schema::create('associate_product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('associate_id')->constrained('associates')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('unit_price', 15, 2); // The custom price
            $table->timestamps();

            // Ensure one price per product per associate
            $table->unique(['associate_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('associate_product_prices');
    }
};
