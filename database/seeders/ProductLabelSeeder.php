<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductLabel;

class ProductLabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labels = [
            [
                'label_name' => 'Standard Retail Tag (50x30mm)',
                'height' => 30,
                'width' => 50,
                'unit' => 'mm',
                'expiry_required' => false,
                'show_product_name' => true,
                'show_sku' => true,
                'show_barcode' => true,
                'show_batch_no' => false,
                'show_mrp' => true,
                'show_logo' => true,
                'status' => true,
            ],
            [
                'label_name' => 'Compact Barcode (40x20mm)',
                'height' => 20,
                'width' => 40,
                'unit' => 'mm',
                'expiry_required' => false,
                'show_product_name' => false,
                'show_sku' => true,
                'show_barcode' => true,
                'show_batch_no' => false,
                'show_mrp' => false,
                'show_logo' => false,
                'status' => true,
            ],
            [
                'label_name' => 'Detailed Expiry Label (60x40mm)',
                'height' => 40,
                'width' => 60,
                'unit' => 'mm',
                'expiry_required' => true,
                'show_product_name' => true,
                'show_sku' => true,
                'show_barcode' => true,
                'show_batch_no' => true,
                'show_mrp' => true,
                'show_logo' => false,
                'status' => true,
            ],
            [
                'label_name' => 'Box Label (4x2 Inch)',
                'height' => 2,
                'width' => 4,
                'unit' => 'inch',
                'expiry_required' => false,
                'show_product_name' => true,
                'show_sku' => true,
                'show_barcode' => true,
                'show_batch_no' => true,
                'show_mrp' => false, // Box labels often don't show individual price
                'show_logo' => true,
                'status' => true,
            ],
        ];

        foreach ($labels as $label) {
            ProductLabel::firstOrCreate(
                ['label_name' => $label['label_name']],
                $label
            );
        }
    }
}
