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
        // 1. Associate Prices
        Schema::create('associate_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('associate_id')->constrained('associates')->onDelete('cascade'); // Customer/Salesperson
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->decimal('unit_price', 15, 2)->default(0); // Custom Associate Unit Price
            $table->decimal('box_price', 15, 2)->default(0);  // Custom Associate Box Price
            $table->decimal('price', 15, 2)->default(0);      // B2B Price?
            $table->decimal('pos_price', 15, 2)->default(0);  // Retail/POS Price
            
            $table->timestamps();
            
            $table->unique(['associate_id', 'product_id']);
        });

        // 2. Group Prices
        Schema::create('group_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('associate_groups')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('box_price', 15, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('pos_price', 15, 2)->default(0);
            
            $table->timestamps();
            
            $table->unique(['group_id', 'product_id']);
        });

        // 3. Promotion Prices
        Schema::create('promotion_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->integer('min_qty')->default(1);
            $table->integer('max_qty')->nullable();
            
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('box_price', 15, 2)->default(0);
            $table->decimal('pos_price', 15, 2)->default(0);
            
            $table->date('start_date');
            $table->date('end_date');
            
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // 4. Price Audit Logs
        Schema::create('price_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('price_type'); // STANDARD, ASSOCIATE, GROUP, PROMOTION
            $table->integer('reference_id')->nullable(); // associate_id or group_id or promotion_id
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->string('field_name'); // e.g. unit_price
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_audit_logs');
        Schema::dropIfExists('promotion_prices');
        Schema::dropIfExists('group_prices');
        Schema::dropIfExists('associate_prices');
    }
};
