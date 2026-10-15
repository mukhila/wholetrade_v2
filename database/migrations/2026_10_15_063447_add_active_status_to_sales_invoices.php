<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'active' to enum; keep legacy values for backward compatibility
        DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('active','draft','confirmed','posted','cancelled','voided') NOT NULL DEFAULT 'active'");

        // Migrate legacy draft/confirmed rows to active
        DB::statement("UPDATE sales_invoices SET status = 'active' WHERE status IN ('draft', 'confirmed')");
    }

    public function down(): void
    {
        DB::statement("UPDATE sales_invoices SET status = 'confirmed' WHERE status = 'active'");
        DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN status ENUM('draft','confirmed','posted','cancelled','voided') NOT NULL DEFAULT 'draft'");
    }
};
