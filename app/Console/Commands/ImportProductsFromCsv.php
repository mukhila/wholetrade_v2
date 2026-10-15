<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductsFromCsv extends Command
{
    protected $signature = 'products:import-csv {--file=products.csv : CSV file name in public folder}';

    protected $description = 'Import products from CSV file - updates name, cost price, sales price, and stock quantity';

    public function handle()
    {
        $fileName = $this->option('file');
        $filePath = public_path($fileName);

        if (!file_exists($filePath)) {
            $this->error("CSV file not found: {$filePath}");
            return 1;
        }

        $this->info("Reading CSV file: {$filePath}");

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Could not open CSV file");
            return 1;
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $this->error("Could not read CSV headers");
            fclose($handle);
            return 1;
        }

        // Map headers to indices
        $headerMap = array_flip($headers);

        $skuIndex = $headerMap['SKU'] ?? null;
        $nameIndex = $headerMap['Name'] ?? null;
        $costPriceIndex = $headerMap['COST PRICE'] ?? null;
        $sellingPriceIndex = $headerMap['SELLING PRICE'] ?? null;
        $stockPcsIndex = $headerMap['STOCK WITH PCS'] ?? null;

        if ($skuIndex === null) {
            $this->error("SKU column not found in CSV");
            fclose($handle);
            return 1;
        }

        $updated = 0;
        $created = 0;
        $skipped = 0;
        $errors = 0;

        $this->info("Starting import...");
        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $progressBar->advance();

                $sku = trim($row[$skuIndex] ?? '');

                // Skip empty SKU rows
                if (empty($sku)) {
                    $skipped++;
                    continue;
                }

                $name = $nameIndex !== null ? trim($row[$nameIndex] ?? '') : '';
                $costPrice = $costPriceIndex !== null ? floatval($row[$costPriceIndex] ?? 0) : 0;
                $sellingPrice = $sellingPriceIndex !== null ? floatval($row[$sellingPriceIndex] ?? 0) : 0;
                $stockPcs = $stockPcsIndex !== null ? floatval($row[$stockPcsIndex] ?? 0) : 0;

                // Skip rows with empty name (like OBProduct row)
                if (empty($name) || $name === 'OBProduct') {
                    $skipped++;
                    continue;
                }

                // Find existing product by SKU
                $product = Product::where('sku', $sku)->first();

                if ($product) {
                    // Update existing product
                    $product->update([
                        'name' => $name,
                        'stock_qty_pcs' => $stockPcs,
                    ]);

                    // Update or create price record
                    ProductPrice::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'cost_price_item' => $costPrice,
                            'sales_price_item' => $sellingPrice,
                        ]
                    );

                    $updated++;
                } else {
                    // Create new product
                    $product = Product::create([
                        'sku' => $sku,
                        'name' => $name,
                        'stock_qty_pcs' => $stockPcs,
                        'stock_qty_box' => 0,
                    ]);

                    // Create price record
                    ProductPrice::create([
                        'product_id' => $product->id,
                        'cost_price_item' => $costPrice,
                        'sales_price_item' => $sellingPrice,
                    ]);

                    $created++;
                }
            }

            DB::commit();
            $progressBar->finish();

            $this->newLine(2);
            $this->info("Import completed successfully!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Updated', $updated],
                    ['Created', $created],
                    ['Skipped', $skipped],
                    ['Errors', $errors],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $progressBar->finish();
            $this->newLine();
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        } finally {
            fclose($handle);
        }
    }
}
