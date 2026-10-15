<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN payment_status ENUM('unpaid', 'partial', 'paid', 'voided') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        DB::statement("UPDATE sales_invoices SET payment_status = 'unpaid' WHERE payment_status = 'voided'");
        DB::statement("ALTER TABLE sales_invoices MODIFY COLUMN payment_status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid'");
    }
};
