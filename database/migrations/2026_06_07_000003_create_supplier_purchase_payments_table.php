<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One payment can cover multiple invoices
        Schema::create('supplier_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id')->nullable()->index();
            $table->foreignId('supplier_id')->constrained('associates')->onDelete('restrict');
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('payment_mode', ['Cash', 'Online Transfer', 'GIRO'])->default('Cash');
            $table->string('reference_number')->nullable();  // for Online Transfer & GIRO
            $table->date('giro_date')->nullable();           // for GIRO
            $table->boolean('giro_is_paid')->default(false); // manually mark GIRO as paid
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // Lines: which invoice got how much from this payment
        Schema::create('supplier_purchase_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('supplier_purchase_payments')->onDelete('cascade');
            $table->foreignId('purchase_id')->constrained('supplier_purchases')->onDelete('restrict');
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_purchase_payment_items');
        Schema::dropIfExists('supplier_purchase_payments');
    }
};
