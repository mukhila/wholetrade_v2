<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration:
     * 1. Adds gross_total column to store line items total BEFORE discount
     * 2. Fixes existing data where subtotal was incorrectly storing post-discount value
     * 3. Recalculates net_total for data consistency
     */
    public function up(): void
    {
        // Step 1: Add gross_total column
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('gross_total', 15, 2)->default(0)->after('reference_number');
        });

        // Step 2: Fix existing data
        // Old: subtotal stored (gross - discount), which was wrong
        // New: subtotal = line items total, net_total = subtotal - discount + tax
        DB::statement('
            UPDATE sales_invoices
            SET gross_total = subtotal + discount_amount
        ');
        DB::statement('
            UPDATE sales_invoices
            SET subtotal = gross_total,
                round_off = 0,
                net_total = gross_total - discount_amount + tax_amount
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('gross_total');
        });
    }
};
