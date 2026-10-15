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
        Schema::create('supplier_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('associates')->onDelete('cascade');
            $table->string('transaction_number')->unique();
            $table->date('transaction_date');
            
            // Header Fields
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete(); // Default Tax
            
            // Addresses
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            
            // Financials
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('round_off', 15, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);
            
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'submitted', 'posted'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_credit_note_id')->constrained('supplier_credit_notes')->onDelete('cascade');
            
            // Item Details
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete(); // For Product based
            $table->string('gl_account')->nullable(); // For Service based
            $table->text('description');
            $table->text('narration')->nullable();
            
            // Calculation
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0); // quantity * unit_price (before tax?) typically total usually implies line total. Let's say (qty*price)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_note_items');
        Schema::dropIfExists('supplier_credit_notes');
    }
};
