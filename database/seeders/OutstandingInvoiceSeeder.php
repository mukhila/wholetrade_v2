<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\InvoiceReference;
use App\Models\Associate;

class OutstandingInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csvFile = public_path('outsandinginvoice.csv');

        if (!File::exists($csvFile)) {
            $this->command->error("CSV file not found at: $csvFile");
            return;
        }

        $header = null;
        $data = [];
        
        if (($handle = fopen($csvFile, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$header) {
                    $header = $row; // InvoiceNo,Date,Customer Name,Net Total,Paid,Balance
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        $count = 0;
        foreach ($data as $row) {
            // Parse Date: 25-01-2026 -> 2026-01-25
            try {
                $date = Carbon::createFromFormat('d-m-Y', $row['Date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $this->command->warn("Skipping row with invalid date: " . $row['Date']);
                continue;
            }

            // Find Customer by Name
            // Try exact match first
            // Note: Associate likely uses global scope for subscriber, but seeder runs without user context? 
            // Usually seeders run as 'admin' or without scopes if not auth'd. 
            // However, Associate uses HasSubscriberScope trait. 
            // Typically we should look up without global scope if we want to be safe, 
            // OR we assume there's only one subscriber or we pick the first match.
            // Let's try to find an associate by name.
            
            $customerName = trim($row['Customer Name']);
            $customer = Associate::withoutGlobalScope('subscriber')->where('name', $customerName)->first();

            // Status Logic
            $balance = floatval($row['Balance']);
            $status = $balance > 0 ? ($balance < floatval($row['Net Total']) ? 'PARTIAL' : 'OPEN') : 'CLOSED';

            InvoiceReference::updateOrCreate(
                ['invoice_no' => $row['InvoiceNo']],
                [
                    'date' => $date,
                    'customer_id' => $customer ? $customer->id : null, 
                    'customer_name' => $customerName,
                    'net_total' => $row['Net Total'],
                    'paid' => $row['Paid'],
                    'balance' => $row['Balance'],
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $count++;
        }

        $this->command->info("Imported $count outstanding invoices successfully.");
    }
}
