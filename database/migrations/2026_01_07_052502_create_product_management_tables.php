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
        // 1. Products (Core)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('product_type', ['STANDARD', 'VARIANT', 'COMPOSITE'])->default('STANDARD');
            
            // Foreign Keys
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('associates')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            
            $table->string('barcode')->nullable()->unique();
            $table->decimal('weight', 10, 2)->nullable();
            
            // Assuming pack_size points to sizes table as per prompt suggestion, though "Units per box" usually implies an integer. 
            // The prompt says "Pack Size (Units per box)" AND "pack_size_id (size table) optional". 
            // I will add both to be safe: a pack_size_qty and a pack_size_id. 
            // Use pack_size_id for the dimension/size name (e.g. "XL Box") and pack_size_qty for the count (e.g. 12).
            $table->foreignId('pack_size_id')->nullable()->constrained('sizes')->nullOnDelete();
            $table->integer('pack_size_qty')->default(1); 

            $table->boolean('status')->default(true);
            
            // Inventory Config in core table as per prompt
            $table->boolean('inventory_control')->default(true);
            $table->integer('min_stock_qty')->default(0);
            $table->integer('reorder_qty')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Product Prices
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            // Cost
            $table->decimal('cost_price_item', 15, 2)->default(0);
            $table->decimal('cost_price_box', 15, 2)->default(0);
            $table->string('margin_type')->default('percentage'); // percentage or fixed
            $table->decimal('margin', 15, 2)->default(0);
            
            // Sales
            $table->decimal('sales_price_item', 15, 2)->default(0);
            $table->decimal('sales_price_box', 15, 2)->default(0);
            $table->decimal('min_sales_price_item', 15, 2)->default(0);
            $table->decimal('min_sales_price_box', 15, 2)->default(0);
            
            // Retail
            $table->decimal('retail_price_item', 15, 2)->default(0);
            $table->decimal('retail_price_box', 15, 2)->default(0);
            $table->decimal('min_retail_price_item', 15, 2)->default(0);
            $table->decimal('min_retail_price_box', 15, 2)->default(0);
            
            $table->timestamps();
        });

        // 3. Product Inventory (Real-time data)
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->integer('stock_in_hand')->default(0);
            $table->decimal('stock_value', 15, 2)->default(0);
            
            $table->boolean('have_batch')->default(false);
            $table->boolean('have_serial')->default(false);
            $table->string('lot_no')->nullable();
            
            $table->timestamps();
        });

        // 4. Product Variants
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_product_id')->constrained('products')->onDelete('cascade');
            
            $table->string('variant_sku')->unique();
            $table->foreignId('size_id')->nullable()->constrained('sizes')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            
            $table->string('barcode')->nullable()->unique();
            
            // Optional Price Override
            $table->decimal('price_override', 15, 2)->nullable();
            
            $table->integer('stock')->default(0);
            
            $table->timestamps();
        });

        // 5. Composite Products
        Schema::create('composite_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composite_product_id')->constrained('products')->onDelete('cascade'); // The parent/bundle
            $table->foreignId('component_product_id')->constrained('products')->onDelete('cascade'); // The child/component
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        // 6. Product Images
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('composite_products');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_inventories');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
    }
};
