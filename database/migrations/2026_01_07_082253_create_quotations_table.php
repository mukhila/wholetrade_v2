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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('salesperson_id')->constrained('users');
            $table->foreignId('customer_id')->constrained('associates'); // Assuming associates table holds customers
            $table->enum('status', ['PENDING_ADMIN_APPROVAL', 'APPROVED_BY_ADMIN', 'REVISED_BY_ADMIN', 'REJECTED', 'CONVERTED_TO_INVOICE'])->default('PENDING_ADMIN_APPROVAL');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->text('remarks')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->enum('unit_type', ['Item', 'Box'])->default('Item');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2); // Price per unit/box at time of quotation
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
