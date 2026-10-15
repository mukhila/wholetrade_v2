<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_purchases', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->unique(['supplier_id', 'invoice_number'], 'supplier_purchases_supplier_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_purchases', function (Blueprint $table) {
            $table->dropUnique('supplier_purchases_supplier_invoice_unique');
            $table->unique('invoice_number');
        });
    }
};
