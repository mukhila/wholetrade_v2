<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * CleanTestDataByDateSeeder
 *
 * Selectively removes invoice data based on date criteria.
 * This is safer than truncating all data.
 *
 * Usage:
 *   php artisan db:seed --class=CleanTestDataByDateSeeder
 *
 * Configuration:
 *   - Modify $fromDate and $toDate below to target specific test data
 *   - Or use invoice_number patterns to identify test invoices
 */
class CleanTestDataByDateSeeder extends Seeder
{
    // ====================================================================
    // CONFIGURATION - Modify these to target your test data
    // ====================================================================

    // Option 1: Delete by date range
    protected ?string $fromDate = '2026-01-01';  // Start date (inclusive)
    protected ?string $toDate = '2026-12-31';    // End date (inclusive)

    // Option 2: Delete by invoice number pattern (e.g., 'TEST-', 'INV-000')
    protected ?string $invoiceNumberPattern = null; // e.g., 'TEST-%' or 'INV-000%'

    // Option 3: Delete by customer IDs (test customers)
    protected array $testCustomerIds = []; // e.g., [1, 2, 3]

    // ====================================================================

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('This seeder cannot run in production!');
            return;
        }

        $this->command->info('Starting selective test data cleanup...');
        $this->command->newLine();

        // Build query to find target invoices
        $invoiceQuery = DB::table('sales_invoices');

        if ($this->fromDate && $this->toDate) {
            $invoiceQuery->whereBetween('invoice_date', [$this->fromDate, $this->toDate]);
            $this->command->info("Targeting invoices from {$this->fromDate} to {$this->toDate}");
        }

        if ($this->invoiceNumberPattern) {
            $invoiceQuery->where('invoice_number', 'like', $this->invoiceNumberPattern);
            $this->command->info("Targeting invoices matching pattern: {$this->invoiceNumberPattern}");
        }

        if (!empty($this->testCustomerIds)) {
            $invoiceQuery->whereIn('customer_id', $this->testCustomerIds);
            $this->command->info("Targeting invoices for customer IDs: " . implode(', ', $this->testCustomerIds));
        }

        $targetInvoiceIds = $invoiceQuery->pluck('id')->toArray();
        $targetInvoiceCount = count($targetInvoiceIds);

        if ($targetInvoiceCount === 0) {
            $this->command->warn('No invoices found matching the criteria.');
            return;
        }

        $this->command->info("Found {$targetInvoiceCount} invoices to delete.");
        $this->command->newLine();

        if (!$this->command->confirm('Do you want to proceed with deletion?', false)) {
            $this->command->info('Operation cancelled.');
            return;
        }

        DB::beginTransaction();

        try {
            $stats = [
                'audit_logs' => 0,
                'receipt_allocations' => 0,
                'invoice_items' => 0,
                'receipts' => 0,
                'invoices' => 0,
            ];

            // Step 1: Find related receipts (via allocations)
            $relatedReceiptIds = DB::table('sales_receipt_allocations')
                ->whereIn('sales_invoice_id', $targetInvoiceIds)
                ->pluck('sales_receipt_id')
                ->unique()
                ->toArray();

            // Step 2: Delete audit logs for these invoices
            $this->command->info('Deleting audit logs...');
            $stats['audit_logs'] = DB::table('audit_logs')
                ->where(function ($q) use ($targetInvoiceIds, $relatedReceiptIds) {
                    $q->where(function ($q2) use ($targetInvoiceIds) {
                        $q2->where('reference_type', 'like', '%SalesInvoice%')
                           ->whereIn('reference_id', $targetInvoiceIds);
                    })->orWhere(function ($q2) use ($relatedReceiptIds) {
                        $q2->where('reference_type', 'like', '%SalesReceipt%')
                           ->whereIn('reference_id', $relatedReceiptIds);
                    });
                })
                ->delete();

            // Step 3: Delete receipt allocations
            $this->command->info('Deleting receipt allocations...');
            $stats['receipt_allocations'] = DB::table('sales_receipt_allocations')
                ->whereIn('sales_invoice_id', $targetInvoiceIds)
                ->delete();

            // Step 4: Delete invoice items
            $this->command->info('Deleting invoice items...');
            $stats['invoice_items'] = DB::table('sales_invoice_items')
                ->whereIn('sales_invoice_id', $targetInvoiceIds)
                ->delete();

            // Step 5: Delete orphaned receipts (receipts with no remaining allocations)
            $this->command->info('Deleting orphaned receipts...');
            $receiptsWithAllocations = DB::table('sales_receipt_allocations')
                ->pluck('sales_receipt_id')
                ->unique()
                ->toArray();

            $orphanedReceiptIds = array_diff($relatedReceiptIds, $receiptsWithAllocations);
            $stats['receipts'] = DB::table('sales_receipts')
                ->whereIn('id', $orphanedReceiptIds)
                ->delete();

            // Step 6: Delete invoices
            $this->command->info('Deleting invoices...');
            $stats['invoices'] = DB::table('sales_invoices')
                ->whereIn('id', $targetInvoiceIds)
                ->delete();

            DB::commit();

            $this->command->newLine();
            $this->command->info('=========================================');
            $this->command->info('Selective cleanup completed!');
            $this->command->info('=========================================');
            $this->command->newLine();

            $this->command->table(
                ['Table', 'Records Deleted'],
                [
                    ['audit_logs', $stats['audit_logs']],
                    ['sales_receipt_allocations', $stats['receipt_allocations']],
                    ['sales_invoice_items', $stats['invoice_items']],
                    ['sales_receipts (orphaned)', $stats['receipts']],
                    ['sales_invoices', $stats['invoices']],
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            $this->command->error('All changes rolled back.');
            throw $e;
        }
    }
}
