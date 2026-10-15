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
     * Add 'confirmed' status to sales_invoices enum.
     * Workflow: draft (editable) → confirmed (locked, inventory deducted) → payment (partial/paid)
     */
    public function up(): void
    {
        // MySQL requires modifying the enum to add new values
        DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('draft', 'confirmed', 'posted', 'cancelled') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'confirmed' records to 'posted' before removing the enum value
        DB::table('sales_invoices')->where('status', 'confirmed')->update(['status' => 'posted']);

        DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('draft', 'posted', 'cancelled') DEFAULT 'draft'");
    }
};
