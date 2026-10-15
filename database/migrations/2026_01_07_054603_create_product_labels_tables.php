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
        Schema::create('product_labels', function (Blueprint $table) {
            $table->id();
            $table->string('label_name')->unique();
            $table->decimal('height', 8, 2);
            $table->decimal('width', 8, 2);
            $table->enum('unit', ['mm', 'cm', 'inch'])->default('mm');
            
            // Expiry Config
            $table->boolean('expiry_required')->default(false);
            
            // Content Toggles (Optional contents mentioned in specs)
            $table->boolean('show_product_name')->default(true);
            $table->boolean('show_sku')->default(true);
            $table->boolean('show_barcode')->default(true);
            $table->boolean('show_batch_no')->default(false);
            $table->boolean('show_mrp')->default(false);
            $table->boolean('show_logo')->default(false);
            
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_label_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('label_id')->constrained('product_labels')->onDelete('cascade');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            // Prevent duplicate assignments
            $table->unique(['product_id', 'label_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_label_assignments');
        Schema::dropIfExists('product_labels');
    }
};
