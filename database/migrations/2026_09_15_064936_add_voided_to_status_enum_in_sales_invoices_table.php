<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('draft', 'confirmed', 'posted', 'cancelled', 'voided') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("UPDATE sales_invoices SET status = 'cancelled' WHERE status = 'voided'");
        DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('draft', 'confirmed', 'posted', 'cancelled') NOT NULL DEFAULT 'draft'");
    }
};
