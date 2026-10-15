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
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('pi_number')->unique(); // PI-XXXX
            $table->date('pi_date');
            $table->date('valid_till')->nullable();
            
            // Linkages
            $table->foreignId('customer_id')->constrained('associates')->onDelete('cascade');
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            
            // Addresses
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            
            $table->string('reference_number')->nullable(); // Customer Ref
            
            // Amounts
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('round_off', 8, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);
            
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'submitted', 'converted', 'expired', 'cancelled'])->default('draft');
            
            // Conversion Tracking
            $table->boolean('is_converted')->default(false);
            $table->foreignId('converted_to_quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            // Future: converted_to_invoice_id, etc.
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('proforma_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')->constrained('proforma_invoices')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->text('item_description')->nullable();
            
            // Qty
            $table->decimal('box_qty', 15, 2)->default(0);
            $table->decimal('item_qty', 15, 2)->default(0);
            $table->decimal('total_qty', 15, 2)->default(0); // Loose Total
            
            // Pricing
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0); // Estimated tax portion for line
            $table->decimal('total_price', 15, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
        Schema::dropIfExists('proforma_invoices');
    }
};
