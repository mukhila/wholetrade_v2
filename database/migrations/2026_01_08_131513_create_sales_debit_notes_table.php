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
        Schema::create('sales_debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('associates')->onDelete('cascade');
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Optional: Link to original invoice
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            
            $table->string('transaction_number')->unique(); // SDN-XXXXX
            $table->date('transaction_date');
            
            // Header Fields
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            
            // Addresses
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            
            // Financials
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('round_off', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'submitted', 'posted'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sales_debit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_debit_note_id')->constrained('sales_debit_notes')->onDelete('cascade');
            
            // Service / Item Details
            $table->string('gl_account')->nullable(); // For accounting integration
            $table->text('description');
            $table->text('narration')->nullable();
            
            // Pricing
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            
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
        Schema::dropIfExists('sales_debit_note_items');
        Schema::dropIfExists('sales_debit_notes');
    }
};
