<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\UnitOfMeasure;
use App\Models\ProductBrand;
use App\Models\Associate;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Dependencies Exist
        
        // Category
        $vegCategory = ItemCategory::firstOrCreate(
            ['name' => 'Vegetables'],
            ['status' => true]
        );

        // UOMs
        // UOMs
        $kgUom = UnitOfMeasure::firstOrCreate(
            ['name' => 'Kilogram'],
            ['status' => true]
        );
         $bunchUom = UnitOfMeasure::firstOrCreate(
            ['name' => 'Bunch'],
            ['status' => true]
        );

        // Brand (Generic)
        $brand = ProductBrand::firstOrCreate(
            ['name' => 'Fresh Farm'],
            ['status' => true]
        );

        // Supplier
        $supplier = Associate::firstOrCreate(
            ['email' => 'supplier@freshfarm.com'],
            [
                'type' => 'Supplier',
                'name' => 'Fresh Farm Suppliers',
                'code' => 'SUP-0001',
                'address_line_1' => '123 Farm Lane',
                'phone' => '1234567890',
                'password' => bcrypt('password'),
                'currency_id' => 1, // Assuming currency ID 1 exists
                'status' => true
            ]
        );


        // 2. Define Vegetable Products
        $vegetables = [
            [
                'name' => 'Tomato (Local)',
                'uom_id' => $kgUom->id,
                'weight' => 1.00,
                'cost_price_item' => 0.50,
                'sales_price_item' => 0.80,
                'retail_price_item' => 1.00,
            ],
            [
                'name' => 'Potato (Large)',
                'uom_id' => $kgUom->id,
                'weight' => 1.00,
                'cost_price_item' => 0.40,
                'sales_price_item' => 0.60,
                'retail_price_item' => 0.80,
            ],
            [
                'name' => 'Carrot (Orange)',
                'uom_id' => $kgUom->id,
                'weight' => 1.00,
                'cost_price_item' => 0.60,
                'sales_price_item' => 0.90,
                'retail_price_item' => 1.20,
            ],
            [
                'name' => 'Onion (Red)',
                'uom_id' => $kgUom->id,
                'weight' => 1.00,
                'cost_price_item' => 0.45,
                'sales_price_item' => 0.70,
                'retail_price_item' => 0.90,
            ],
            [
                'name' => 'Spinach (Fresh)',
                'uom_id' => $bunchUom->id,
                'weight' => 0.25,
                'cost_price_item' => 0.30,
                'sales_price_item' => 0.50,
                'retail_price_item' => 0.70,
            ],
             [
                'name' => 'Broccoli',
                'uom_id' => $kgUom->id,
                'weight' => 1.00,
                'cost_price_item' => 1.50,
                'sales_price_item' => 2.20,
                'retail_price_item' => 2.80,
            ],
             [
                'name' => 'Cucumber (English)',
                'uom_id' => $kgUom->id,
                'weight' => 1.00,
                'cost_price_item' => 0.70,
                'sales_price_item' => 1.10,
                'retail_price_item' => 1.50,
            ],
             [
                'name' => 'Bell Pepper (Green)',
                'uom_id' => $kgUom->id,
                'weight' => 1.00,
                'cost_price_item' => 1.20,
                'sales_price_item' => 1.80,
                'retail_price_item' => 2.20,
            ],
        ];

        // 3. Create Products
        foreach ($vegetables as $index => $veg) {
            $sku = 'VEG-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            
            // Core Product
            $product = Product::create([
                'sku' => $sku,
                'name' => $veg['name'],
                'description' => 'Fresh ' . $veg['name'] . ' sourced daily.',
                'product_type' => 'STANDARD',
                'category_id' => $vegCategory->id,
                'brand_id' => $brand->id,
                'supplier_id' => $supplier->id,
                'uom_id' => $veg['uom_id'],
                'weight' => $veg['weight'],
                'status' => true,
                'inventory_control' => true,
                'min_stock_qty' => 10,
                'reorder_qty' => 50,
            ]);

            // Prices
            $product->price()->create([
                'cost_price_item' => $veg['cost_price_item'],
                'sales_price_item' => $veg['sales_price_item'],
                'retail_price_item' => $veg['retail_price_item'],
                'min_sales_price_item' => $veg['sales_price_item'] * 0.9, // 10% less
                'min_retail_price_item' => $veg['retail_price_item'] * 0.9,
                'margin_type' => 'percentage',
                'margin' => 30, // Example
            ]);

            // Inventory
            $product->inventory()->create([
                'stock_in_hand' => rand(50, 200), // Random stock
                'stock_value' => 0, // Should be calculated, but 0 for seeder
                'have_batch' => false,
                'have_serial' => false,
            ]);
        }
    }
}
