<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\ProductPrice;
use App\Models\UnitOfMeasure; // Assuming UOM model exists
use Illuminate\Support\Facades\DB;

class ProductImportSeeder extends Seeder
{
    public function run(): void
    {
        $file = public_path('storage/extracted_products.txt');
        if (!file_exists($file)) {
            $this->command->error("File not found.");
            return;
        }

        $content = file_get_contents($file);
        // Normalize
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Remove known headers/footers to avoid noise
        $noise = [
            'Product List Report', 'WHOLESALE TRADERS PTE LTD', 
            'singapore 669569', 'Comp Reg No :', 'Print Date :', 'Page No :', 
            'Tel :', '+65 84427604', '202415901E', 'Code Name Category SubCategory Brand Boxcount UnitCost',
            'BUILDING,', 'LAM SOON INDUSTRIAL', '63 HILLVIEW AVENUE,'
        ];
        foreach ($noise as $n) {
            $content = str_ireplace($n, '', $content);
        }

        // Split by 4-digit code at start of record
        // Pattern: space followed by 4 digits followed by space (usually)
        // But headers might have dates like 2026.
        $parts = preg_split('/(\s\d{4}\s)/', ' ' . $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        $currentCategory = ItemCategory::firstOrCreate(['name' => 'General'])->id;
        $count = 0;
        
        // Known categories from manual inspection of PDF/Text
        $knownCategories = [
            'Atta, Rice, Oil & Dals', 
            'Masala, Dry Fruits & More', 
            'Dairy, Bread & Eggs', 
            'Ready To Cook', 
            'Fruit Juices & Drinks', 
            'Household Item', 
            'Organic & Healthy', 
            'Water',
            'Beverages',
            'Personal Care',
            'Cleaning Supplies',
            'Pooja Items',
            'Biscuits & Snacks'
        ];

        // Ensure these exist
        foreach ($knownCategories as $catName) {
            ItemCategory::firstOrCreate(['name' => $catName]);
        }

        // Processing
        // $parts will be [ '0205 ', 'OMAM WATER...', '0212 ', 'ROSE WATER...' ]
        
        for ($i = 0; $i < count($parts); $i++) {
            $segment = trim($parts[$i]);
            
            // Check if segment is just an ID like '0205'
            if (preg_match('/^\d{4}$/', $segment)) {
                if (isset($parts[$i+1])) {
                    $id = $segment;
                    $rest = trim($parts[$i+1]);
                    $i++; // Skip next part as we consumed it

                    // Logic to parse $rest: Name [Category] BoxCount Cost
                    // Cost is last decimal/int.
                    // BoxCount is int before Cost.
                    
                    $cost = 0;
                    $boxCount = 1;
                    $name = $rest;
                    
                    if (preg_match('/(\d+)\s+([\d.]+)\s*$/', $rest, $matches)) {
                        $boxCount = $matches[1];
                        $cost = $matches[2];
                        $name = trim(substr($rest, 0, strrpos($rest, $matches[0])));
                    }

                    // Check for Category in Name
                    $category_id = $currentCategory; // Default
                    foreach ($knownCategories as $cat) {
                        if (stripos($name, $cat) !== false) {
                            $catModel = ItemCategory::where('name', $cat)->first();
                            if ($catModel) {
                                $category_id = $catModel->id;
                                $currentCategory = $category_id; // Update current
                            }
                            $name = trim(str_ireplace($cat, '', $name));
                            break;
                        }
                    }
                    
                    // Create Product
                    try {
                        $product = Product::updateOrCreate(
                            ['sku' => $id],
                            [
                                'name' => substr($name, 0, 255),
                                'product_type' => 'STANDARD',
                                'category_id' => $category_id,
                                'pack_size_qty' => $boxCount,
                                'status' => true,
                                'inventory_control' => true
                            ]
                        );

                        // Create Price
                        ProductPrice::updateOrCreate(
                            ['product_id' => $product->id],
                            [
                                'cost_price_item' => $cost, // Assuming unit cost
                                'sales_price_item' => floatval($cost) * 1.2, // Dummy margin
                            ]
                        );
                        $count++;
                    } catch (\Exception $e) {
                         // ignore duplicates
                    }
                }
            }
        }
        
        $this->command->info("Imported $count products.");
    }
}
