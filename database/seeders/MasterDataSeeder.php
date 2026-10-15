<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Currency
        \App\Models\Currency::create(['name' => 'Singapore Dollar', 'code' => 'SGD', 'is_default' => true]);
        \App\Models\Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'is_default' => false]);

        // 2. Timezone
        \App\Models\Timezone::create(['timezone' => 'Asia/Singapore', 'is_default' => true]);
        \App\Models\Timezone::create(['timezone' => 'UTC', 'is_default' => false]);

        // 3. Tax
        \App\Models\Tax::create(['name' => 'GST', 'percentage' => 9.00, 'type' => 'inclusive']);
        \App\Models\Tax::create(['name' => 'VAT', 'percentage' => 5.00, 'type' => 'exclusive']);

        // 4. Term
        \App\Models\Term::create(['name' => 'Net 30', 'no_of_days' => 30]);
        \App\Models\Term::create(['name' => 'Net 60', 'no_of_days' => 60]);

        // 5. Incoterms
        \App\Models\Incoterm::create(['name' => 'FOB', 'no_of_days' => 0]);
        \App\Models\Incoterm::create(['name' => 'CIF', 'no_of_days' => 0]);

        // 6. Paymode
        \App\Models\Paymode::create(['name' => 'Bank Transfer']);
        \App\Models\Paymode::create(['name' => 'Cheque']);
        \App\Models\Paymode::create(['name' => 'Credit Card']);

        // 7. Bank
        \App\Models\Bank::create(['name' => 'DBS Bank']);
        \App\Models\Bank::create(['name' => 'OCBC Bank']);
        \App\Models\Bank::create(['name' => 'UOB']);

        // 8. Product Brands
        \App\Models\ProductBrand::create(['name' => 'Apple']);
        \App\Models\ProductBrand::create(['name' => 'Samsung']);
        \App\Models\ProductBrand::create(['name' => 'Sony']);

        // 9. Item Category
        \App\Models\ItemCategory::create(['name' => 'Electronics']);
        \App\Models\ItemCategory::create(['name' => 'Furniture']);
        \App\Models\ItemCategory::create(['name' => 'Clothing']);

        // 10. UOM (Unit of Measure)
        \App\Models\UnitOfMeasure::create(['name' => 'Pcs']);
        \App\Models\UnitOfMeasure::create(['name' => 'Kg']);
        \App\Models\UnitOfMeasure::create(['name' => 'Ltr']);
        \App\Models\UnitOfMeasure::create(['name' => 'Box']);

        // 11. Color
        \App\Models\Color::create(['name' => 'Red']);
        \App\Models\Color::create(['name' => 'Blue']);
        \App\Models\Color::create(['name' => 'Black']);
        \App\Models\Color::create(['name' => 'White']);

        // 12. Size
        \App\Models\Size::create(['name' => 'S']);
        \App\Models\Size::create(['name' => 'M']);
        \App\Models\Size::create(['name' => 'L']);
        \App\Models\Size::create(['name' => 'XL']);
        \App\Models\Size::create(['name' => 'XXL']);
    }
}
