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
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number')->unique();
            $table->date('delivery_date');
            
            $table->foreignId('customer_id')->constrained('associates')->onDelete('cascade');
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            
            // References
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete(); // Sales Order Reference
            
            // Header Financials (Optional but requested)
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('total_amount', 15, 2)->default(0); // Value
            
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('remarks')->nullable();
            
            $table->enum('status', ['draft', 'submitted', 'cancelled', 'invoiced'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            // Quantity Handling
            $table->integer('box_qty')->default(0);
            $table->integer('item_qty')->default(0);
            $table->integer('total_qty_loose')->default(0); // For easy stock deduction
            
            // Pricing (Optional snapshot)
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            
            $table->text('item_description')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
    }
};
