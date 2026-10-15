<?php

namespace App\Console\Commands;

use App\Models\Associate;
use App\Models\InvoiceReference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportCustomersAndInvoices extends Command
{
    protected $signature = 'import:customers-invoices
                            {--customers-only : Only import customers}
                            {--invoices-only : Only import invoices}';

    protected $description = 'Import customers from customers.csv and invoices from refrence_invoice.csv';

    public function handle()
    {
        $customersOnly = $this->option('customers-only');
        $invoicesOnly = $this->option('invoices-only');

        if (!$invoicesOnly) {
            $this->importCustomers();
        }

        if (!$customersOnly) {
            $this->importInvoices();
        }

        return 0;
    }

    private function importCustomers()
    {
        $this->info("\n=== Importing Customers from customers.csv ===\n");

        $filePath = public_path('customers.csv');
        if (!file_exists($filePath)) {
            $this->error("customers.csv not found in public folder");
            return;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Could not open customers.csv");
            return;
        }

        // Read header
        $headers = fgetcsv($handle);
        $headerMap = array_flip($headers);

        $idIndex = $headerMap['ID'] ?? null;
        $nameIndex = $headerMap['Name'] ?? null;
        $addressIndex = $headerMap['Address'] ?? null;

        if ($idIndex === null || $nameIndex === null) {
            $this->error("Required columns (ID, Name) not found in CSV");
            fclose($handle);
            return;
        }

        $updated = 0;
        $created = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $id = trim($row[$idIndex] ?? '');
                $name = trim($row[$nameIndex] ?? '');
                $address = $addressIndex !== null ? trim($row[$addressIndex] ?? '') : '';

                // Skip empty rows
                if (empty($id) || empty($name)) {
                    $skipped++;
                    continue;
                }

                // Clean up address (remove line breaks)
                $address = preg_replace('/\s+/', ' ', $address);

                // Find existing associate by username (ID) or name
                $associate = Associate::withoutGlobalScope('subscriber')
                    ->where('username', $id)
                    ->orWhere('name', $name)
                    ->first();

                if ($associate) {
                    // Update existing
                    $associate->update([
                        'username' => $id,
                        'name' => $name,
                        'address_line_1' => $address ?: $associate->address_line_1,
                    ]);
                    $updated++;
                } else {
                    // Create new customer
                    Associate::create([
                        'username' => $id,
                        'name' => $name,
                        'address_line_1' => $address,
                        'type' => 'Customer',
                        'status' => true,
                        'code' => $id,
                    ]);
                    $created++;
                }
            }

            DB::commit();
            fclose($handle);

            $this->info("Customers Import Complete!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Updated', $updated],
                    ['Created', $created],
                    ['Skipped', $skipped],
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Error importing customers: " . $e->getMessage());
        }
    }

    private function importInvoices()
    {
        $this->info("\n=== Importing Invoices from refrence_invoice.csv ===\n");

        $filePath = public_path('refrence_invoice.csv');
        if (!file_exists($filePath)) {
            $this->error("refrence_invoice.csv not found in public folder");
            return;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Could not open refrence_invoice.csv");
            return;
        }

        // Read header
        $headers = fgetcsv($handle);
        $headerMap = array_flip($headers);

        $invoiceNoIndex = $headerMap['InvoiceNo'] ?? null;
        $dateIndex = $headerMap['Date'] ?? null;
        $customerNameIndex = $headerMap['Customer Name'] ?? null;
        $netTotalIndex = $headerMap['Net Total'] ?? null;
        $paidIndex = $headerMap['Paid'] ?? null;
        $balanceIndex = $headerMap['Balance'] ?? null;

        if ($invoiceNoIndex === null || $customerNameIndex === null) {
            $this->error("Required columns not found in CSV");
            fclose($handle);
            return;
        }

        // Pre-load all associates for matching (case-insensitive)
        $associates = Associate::withoutGlobalScope('subscriber')
            ->where('type', 'Customer')
            ->get()
            ->keyBy(function ($item) {
                return strtolower(trim($item->name));
            });

        $imported = 0;
        $updated = 0;
        $notMatched = 0;
        $skipped = 0;
        $unmatchedCustomers = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $invoiceNo = trim($row[$invoiceNoIndex] ?? '');
                $customerName = trim($row[$customerNameIndex] ?? '');

                // Skip empty rows
                if (empty($invoiceNo) || empty($customerName)) {
                    $skipped++;
                    continue;
                }

                // Parse date (format: DD-MM-YYYY)
                $dateStr = trim($row[$dateIndex] ?? '');
                $date = null;
                if ($dateStr) {
                    try {
                        $date = Carbon::createFromFormat('d-m-Y', $dateStr)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $date = null;
                    }
                }

                $netTotal = floatval($row[$netTotalIndex] ?? 0);
                $paid = floatval($row[$paidIndex] ?? 0);
                $balance = floatval($row[$balanceIndex] ?? 0);

                // Find matching associate by name (case-insensitive)
                $customerKey = strtolower(trim($customerName));
                $associate = $associates->get($customerKey);

                $customerId = null;
                if ($associate) {
                    $customerId = $associate->id;
                } else {
                    // Try partial match
                    $associate = $associates->first(function ($item) use ($customerName) {
                        return stripos($item->name, $customerName) !== false ||
                               stripos($customerName, $item->name) !== false;
                    });

                    if ($associate) {
                        $customerId = $associate->id;
                    } else {
                        $notMatched++;
                        if (!in_array($customerName, $unmatchedCustomers)) {
                            $unmatchedCustomers[] = $customerName;
                        }
                    }
                }

                // Create or update invoice reference
                $existing = InvoiceReference::where('invoice_no', $invoiceNo)->first();

                if ($existing) {
                    $existing->update([
                        'date' => $date,
                        'customer_id' => $customerId,
                        'customer_name' => $customerName,
                        'net_total' => $netTotal,
                        'paid' => $paid,
                        'balance' => $balance,
                    ]);
                    $updated++;
                } else {
                    InvoiceReference::create([
                        'invoice_no' => $invoiceNo,
                        'date' => $date,
                        'customer_id' => $customerId,
                        'customer_name' => $customerName,
                        'net_total' => $netTotal,
                        'paid' => $paid,
                        'balance' => $balance,
                    ]);
                    $imported++;
                }
            }

            DB::commit();
            fclose($handle);

            $this->info("Invoices Import Complete!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Imported', $imported],
                    ['Updated', $updated],
                    ['Not Matched', $notMatched],
                    ['Skipped', $skipped],
                ]
            );

            if (count($unmatchedCustomers) > 0) {
                $this->warn("\nUnmatched Customer Names (no associate found):");
                foreach (array_slice($unmatchedCustomers, 0, 20) as $name) {
                    $this->line("  - {$name}");
                }
                if (count($unmatchedCustomers) > 20) {
                    $this->line("  ... and " . (count($unmatchedCustomers) - 20) . " more");
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Error importing invoices: " . $e->getMessage());
        }
    }
}
