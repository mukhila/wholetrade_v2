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
        Schema::create('purchase_delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('do_number')->unique(); // PDN-XXXX
            $table->date('delivery_date');
            
            // Supplier
            $table->foreignId('supplier_id')->constrained('associates')->onDelete('cascade');
            
            // Who received it
            $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            
            // Addresses
            $table->text('shipping_address')->nullable(); // Our Warehouse Address
            $table->text('billing_address')->nullable(); // Reference
            
            // Ref
            $table->string('supplier_do_reference')->nullable(); // Supplier's Paper DO No
            $table->foreignId('purchase_order_id')->nullable(); // Future link
            
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'submitted', 'verified'])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_delivery_note_id')->constrained('purchase_delivery_notes')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->string('item_description')->nullable();
            
            // Quantities
            $table->decimal('box_qty', 15, 2)->default(0);
            $table->decimal('item_qty', 15, 2)->default(0);
            $table->decimal('total_qty_loose', 15, 2)->default(0); // Used for Stock Increment
            
            // Cost (Optional check)
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_delivery_note_items');
        Schema::dropIfExists('purchase_delivery_notes');
    }
};
