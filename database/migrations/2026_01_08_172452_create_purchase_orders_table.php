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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // PO-XXXX
            $table->date('po_date');
            
            // Supplier
            $table->foreignId('supplier_id')->constrained('associates')->onDelete('cascade');
            
            // Header Info
            $table->string('supplier_ref_no')->nullable();
            $table->date('expected_shipping_date')->nullable();
            
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            
            // Addresses
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            
            // Financials
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0); // Total Tax
            $table->decimal('net_total', 15, 2)->default(0);
            
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'submitted', 'partially_received', 'fully_received', 'closed', 'cancelled'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            
            // Product based
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete(); 
            
            // Service based (if no product_id)
            $table->string('gl_account')->nullable();
            
            $table->text('description')->nullable();
            $table->text('narration')->nullable();
            
            // Quantities
            $table->decimal('box_qty', 15, 2)->default(0);
            $table->decimal('item_qty', 15, 2)->default(0); // Inner/Loose qty
            $table->decimal('total_qty', 15, 2)->default(0); // Total Units Ordered
            
            // Tracking
            $table->decimal('received_qty', 15, 2)->default(0); // For GRN tracking
            
            // Pricing
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            
            // Tax per line
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            
            $table->decimal('total_price', 15, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
