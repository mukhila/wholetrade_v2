<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CleanTestInvoiceDataSeeder
 *
 * Safely removes all test invoice data while maintaining data integrity.
 *
 * IMPORTANT: This is for TESTING/DEVELOPMENT environments ONLY!
 * DO NOT run in production!
 *
 * Tables affected (in deletion order):
 * 1. audit_logs (polymorphic references)
 * 2. sales_receipt_allocations (pivot table)
 * 3. sales_invoice_items (child of invoices)
 * 4. sales_receipts (parent of allocations)
 * 5. sales_invoices (main invoice table)
 * 6. invoice_references (legacy invoices)
 * 7. associate_product_prices (optional - customer pricing)
 */
class CleanTestInvoiceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    if (app()->environment('production')) {
        $this->command->error('This seeder cannot run in production!');
        return;
    }

    // NO TRANSACTION HERE

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    DB::table('sales_receipt_allocations')->truncate();
    DB::table('sales_invoice_items')->truncate();
    DB::table('sales_receipts')->truncate();
    DB::table('sales_credit_notes')->truncate();
    DB::table('sales_credit_note_items')->truncate();
    DB::table('sales_debit_notes')->truncate();
    DB::table('sales_debit_note_items')->truncate();
    DB::table('sales_receipts')->truncate();
    DB::table('sales_receipt_allocations')->truncate();
    DB::table('supplier_credit_notes')->truncate();
    DB::table('supplier_credit_note_items')->truncate();
    DB::table('supplier_debit_notes')->truncate();
    DB::table('supplier_debit_notes')->truncate();
    DB::table('supplier_debit_note_items')->truncate();
    DB::table('sales_invoices')->truncate();   
    DB::table('invoices')->truncate();   
    DB::table('quotations')->truncate();   
    DB::table('quotation_items')->truncate();   
    DB::table('quotation_logs')->truncate();   
    DB::table('associate_product_prices')->truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    $this->command->info('Test invoice data cleanup completed!');
}

}
