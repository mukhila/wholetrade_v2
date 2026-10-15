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
        Schema::table('sales_credit_notes', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')
                  ->references('id')
                  ->on('sales_invoices')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_credit_notes', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')
                  ->references('id')
                  ->on('invoices')
                  ->nullOnDelete();
        });
    }
};
