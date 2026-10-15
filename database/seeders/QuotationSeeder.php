<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Models\Associate;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\ItemCategory;
use App\Models\ProductBrand;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Ensure a Subscriber exists
        $subscriber = Subscriber::firstOrCreate(
            ['email' => 'demo@subscriber.com'],
            [
                'name' => 'Demo Subscriber',
                'phone' => '1234567890',
                'address' => '123 Demo St',
                'plan' => 'Pro',
                'status' => 'active',
                'is_active' => true,
                'subscription_start_date' => now(),
                'subscription_end_date' => now()->addYear(),
            ]
        );

        // Ensure base UOM and Currency exist for the subscriber
        $uom = \App\Models\UnitOfMeasure::firstOrCreate(
            ['name' => 'Each', 'subscriber_id' => $subscriber->id],
            ['status' => true]
        );

        $currency = \App\Models\Currency::where('code', 'USD')->where('subscriber_id', $subscriber->id)->first();
        if (!$currency) {
            // Try creating, but catch duplicates if global unique exists
            try {
                $currency = \App\Models\Currency::create([
                    'code' => 'USD',
                    'subscriber_id' => $subscriber->id,
                    'name' => 'US Dollar',
                    'is_default' => true
                ]);
            } catch (\Exception $e) {
                // If duplicate, try to find ANY USD
                 $currency = \App\Models\Currency::where('code', 'USD')->first();
                 // If still null, create unique
                 if(!$currency) {
                     $currency = \App\Models\Currency::create([
                        'code' => 'USD-DEMO',
                        'subscriber_id' => $subscriber->id,
                        'name' => 'Demo Dollar',
                        'is_default' => true
                    ]);
                 }
            }
        }

        // 1. Get Salesperson
        $salesperson = User::where('email', 'sales@example.com')->first();
        if (!$salesperson) {
            $salesperson = User::create([
                'name' => 'Salesperson User',
                'email' => 'sales@example.com',
                'password' => bcrypt('password'), // Ensure you hash passwords!
                'subscriber_id' => $subscriber->id,
            ]);
        } else {
             // Update subscriber if null?
             if(!$salesperson->subscriber_id) {
                 $salesperson->update(['subscriber_id' => $subscriber->id]);
             }
        }

        // 2. Get or Create Customer
        $customer = Associate::where('username', 'cust001')->where('subscriber_id', $subscriber->id)->first();
        if (!$customer) {
            $customer = Associate::create([
                'subscriber_id' => $subscriber->id,
                'name' => 'Tech Solutions Ltd.',
                'code' => 'CUST001',
                'username' => 'cust001',
                //'contact_person' => 'John Doe', // Check if column exists, Model doesn't show it in fillable
                'email' => 'contact@techsolutions.com',
                'phone' => '1234567890',
                'address_line_1' => '123 Tech Park',
                'type' => 'Customer',
                'status' => true,
                'currency_id' => $currency->id,
                'password' => bcrypt('password'),
            ]);
        }

        // 3. Get Products (Ensure at least 3 exist)
        // We use the subscriber scope manually or pass ID
        $products = Product::where('subscriber_id', $subscriber->id)->where('status', true)->limit(5)->get();
        if ($products->count() < 3) {
             // Safe Category/Brand Retrieval
             $cat = ItemCategory::firstOrCreate(
                 ['name' => 'General', 'subscriber_id' => $subscriber->id],
                 ['status' => true]
             );
             
             $brand = ProductBrand::firstOrCreate(
                 ['name' => 'Generic Brand', 'subscriber_id' => $subscriber->id],
                 ['status' => true]
             );

             // Reuse existing UOM or ensure it
             $uom = \App\Models\UnitOfMeasure::firstOrCreate(
                 ['name' => 'Each', 'subscriber_id' => $subscriber->id],
                 ['status' => true]
             );

             $products = collect();
             for ($i = 1; $i <= 3; $i++) {
                 $p = Product::create([
                     'subscriber_id' => $subscriber->id,
                     'name' => 'Seeded Product ' . $i,
                     'sku' => 'TOK-' . Str::random(5),
                     'category_id' => $cat->id,
                     'brand_id' => $brand->id,
                     'uom_id' => $uom->id,
                     'product_type' => 'STANDARD',
                     'status' => true, 
                     'description' => 'A seeded product.'
                 ]);
                 
                 // Create Dummy Price
                 \App\Models\ProductPrice::create([
                     'subscriber_id' => $subscriber->id,
                     'product_id' => $p->id,
                     'sales_price_item' => 100 * $i,
                     'sales_price_box' => 1000 * $i,
                     'min_sales_price_item' => 90 * $i,
                     'min_sales_price_box' => 900 * $i,
                 ]);
                 
                 $products->push($p);
             }
        }

        // 4. Create Quotations

        // Quote 1: Pending
        $quote1 = Quotation::where('remarks', 'Urgent request for Q1 stock.')->where('subscriber_id', $subscriber->id)->first();
        if (!$quote1) {
            $quote1 = Quotation::create([
                'subscriber_id' => $subscriber->id,
                'salesperson_id' => $salesperson->id,
                'customer_id' => $customer->id,
                'status' => 'PENDING_ADMIN_APPROVAL',
                'subtotal' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'remarks' => 'Urgent request for Q1 stock.',
                'created_at' => now()->subDays(1),
            ]);

            $total1 = 0;
            foreach($products->take(2) as $prod) {
                $qty = rand(5, 20);
                $price = 100.00; // Simplified
                $sub = $qty * $price;
                
                QuotationItem::create([
                    'subscriber_id' => $subscriber->id,
                    'quotation_id' => $quote1->id,
                    'product_id' => $prod->id,
                    'item_qty' => $qty,
                    'box_qty' => 0,
                    'unit_price' => $price,
                    'total_price' => $sub,
                ]);
                $total1 += $sub;
            }
            $quote1->update(['subtotal' => $total1, 'grand_total' => $total1]);
        }

        // Quote 2: Approved / Invoiced
        $quote2 = Quotation::where('remarks', 'Approved monthly supply.')->where('subscriber_id', $subscriber->id)->first();
        if (!$quote2) {
            $quote2 = Quotation::create([
                'subscriber_id' => $subscriber->id,
                'salesperson_id' => $salesperson->id,
                'customer_id' => $customer->id,
                'status' => 'APPROVED_BY_ADMIN',
                'subtotal' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'remarks' => 'Approved monthly supply.',
                'created_at' => now()->subDays(5),
            ]);

            $total2 = 0;
            foreach($products->skip(1)->take(2) as $prod) {
                $qty = rand(10, 50);
                $price = 150.00; 
                $sub = $qty * $price;

                QuotationItem::create([
                    'subscriber_id' => $subscriber->id,
                    'quotation_id' => $quote2->id,
                    'product_id' => $prod->id,
                    'item_qty' => $qty,
                    'box_qty' => 0,
                    'unit_price' => $price,
                    'total_price' => $sub,
                ]);
                $total2 += $sub;
            }
            $quote2->update(['subtotal' => $total2, 'grand_total' => $total2]);
        }

        // Quote 3: Rejected
        $quote3 = Quotation::where('remarks', 'Discontinued items.')->where('subscriber_id', $subscriber->id)->first();
        if (!$quote3) {
            $quote3 = Quotation::create([
                'subscriber_id' => $subscriber->id,
                'salesperson_id' => $salesperson->id,
                'customer_id' => $customer->id,
                'status' => 'REJECTED',
                'subtotal' => 500.00,
                'tax_amount' => 0,
                'grand_total' => 500.00,
                'remarks' => 'Discontinued items.',
                'admin_notes' => 'Stock not available for these items.',
                'created_at' => now()->subDays(3),
            ]);
            
            // Items for Q3...
             QuotationItem::create([
                    'subscriber_id' => $subscriber->id,
                    'quotation_id' => $quote3->id,
                    'product_id' => $products->first()->id,
                    'item_qty' => 5,
                    'box_qty' => 0,
                    'unit_price' => 100.00,
                    'total_price' => 500.00,
                ]);
        }
    }
}
