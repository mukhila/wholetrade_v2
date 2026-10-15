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
        Schema::create('sales_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique(); // RCP-XXXX
            $table->date('receipt_date');
            
            $table->foreignId('customer_id')->constrained('associates')->onDelete('cascade');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            
            $table->decimal('amount', 15, 2)->default(0); // Total Received
            $table->decimal('unused_amount', 15, 2)->default(0); // Amount not yet allocated to invoices
            
            $table->string('payment_mode')->nullable(); // Cash, Bank, Cheque
            $table->string('reference')->nullable(); // Cheque No, Transaction ID
            
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Pivot table or Allocation table to link Receipt to Invoices
        Schema::create('sales_receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_receipt_id')->constrained('sales_receipts')->onDelete('cascade');
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->onDelete('cascade');
            $table->decimal('allocated_amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_receipt_allocations');
        Schema::dropIfExists('sales_receipts');
    }
};
