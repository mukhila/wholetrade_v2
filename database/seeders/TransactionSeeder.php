<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\SalesCreditNote;
use App\Models\SalesCreditNoteItem;
use App\Models\SalesDebitNote;
use App\Models\SalesDebitNoteItem;
use App\Models\SupplierCreditNote;
use App\Models\SupplierCreditNoteItem;
use App\Models\SupplierDebitNote;
use App\Models\SupplierDebitNoteItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Associate;
use App\Models\Product;
use App\Models\Currency;
use App\Models\User;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        
        // Clear old data to prevent duplicate keys
        SalesCreditNoteItem::truncate();
        SalesCreditNote::truncate();
        SalesDebitNoteItem::truncate();
        SalesDebitNote::truncate();
        SupplierCreditNoteItem::truncate();
        SupplierCreditNote::truncate();
        SupplierDebitNoteItem::truncate();
        SupplierDebitNote::truncate();
        DeliveryOrderItem::truncate();
        DeliveryOrder::truncate();
        
        Schema::enableForeignKeyConstraints();

        $customers = Associate::where('type', 'Customer')->get();
        $suppliers = Associate::where('type', 'Supplier')->get();
        $products = Product::all();
        $currency = Currency::where('code', 'USD')->first() ?? Currency::first();
        $salesperson = User::first(); // Just pick first user as salesperson

        if ($customers->isEmpty() || $suppliers->isEmpty() || $products->isEmpty()) {
            $this->command->info('Missing dependencies (Customers, Suppliers, or Products). Skipping Transaction Seeder.');
            return;
        }

        // 1. Sales Credit Notes
        foreach ($customers->take(3) as $index => $customer) {
            $cn = SalesCreditNote::create([
                'customer_id' => $customer->id,
                'salesperson_id' => $salesperson->id,
                'transaction_number' => 'CN-SEED-' . ($index + 100),
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
                'currency_id' => $currency->id,
                'exchange_rate' => 1,
                'billing_address' => $customer->address_line_1,
                'shipping_address' => $customer->address_line_1,
                'subtotal' => 0, // Will update
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'status' => 'submitted',
                'remarks' => 'Seeded Sales Credit Note',
            ]);

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($products->take(2) as $product) {
                $qty = rand(1, 5);
                $price = $product->price->sales_price_item ?? 100;
                $total = $qty * $price;
                $tax = $total * 0.05; // 5% dummy tax

                SalesCreditNoteItem::create([
                    'sales_credit_note_id' => $cn->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'item_qty' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => 5,
                    'tax_amount' => $tax,
                    'total_price' => $total + $tax,
                ]);

                $subtotal += $total;
                $taxTotal += $tax;
            }

            $cn->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'grand_total' => $subtotal + $taxTotal,
            ]);
        }

        // 2. Sales Debit Notes
        foreach ($customers->take(3) as $index => $customer) {
            $sdn = SalesDebitNote::create([
                'customer_id' => $customer->id,
                'salesperson_id' => $salesperson->id,
                'transaction_number' => 'SDN-SEED-' . ($index + 100),
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
                'currency_id' => $currency->id,
                'billing_address' => $customer->address_line_1,
                'subtotal' => 0,
                'grand_total' => 0,
                'status' => 'draft',
                'remarks' => 'Seeded Sales Debit Note (Service/Adjustment)',
            ]);

            $itemTotal = 500;
            
            SalesDebitNoteItem::create([
                'sales_debit_note_id' => $sdn->id,
                'description' => 'Service Adjustment Charge',
                'gl_account' => '4001-SVC',
                'quantity' => 1,
                'unit_price' => $itemTotal,
                'tax_rate' => 0,
                'total_price' => $itemTotal,
            ]);

            $sdn->update([
                'subtotal' => $itemTotal,
                'grand_total' => $itemTotal,
            ]);
        }

        // 3. Supplier Credit Notes
        foreach ($suppliers->take(3) as $index => $supplier) {
            $scn = SupplierCreditNote::create([
                'supplier_id' => $supplier->id,
                'transaction_number' => 'SUP-CN-SEED-' . ($index + 100),
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
                'currency_id' => $currency->id,
                'subtotal' => 0,
                'net_total' => 0,
                'status' => 'submitted',
                'remarks' => 'Seeded Supplier Credit Note',
            ]);

            $subtotal = 0;
            foreach ($products->take(2) as $product) {
                $qty = rand(1, 5);
                $price = $product->price->cost_price_item ?? 50;
                $total = $qty * $price;

                SupplierCreditNoteItem::create([
                    'supplier_credit_note_id' => $scn->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $total,
                ]);

                $subtotal += $total;
            }

            $scn->update([
                'subtotal' => $subtotal,
                'net_total' => $subtotal,
            ]);
        }
        
         // 4. Supplier Debit Notes
         foreach ($suppliers->take(3) as $index => $supplier) {
            $sdn = SupplierDebitNote::create([
                'supplier_id' => $supplier->id,
                'transaction_number' => 'SUP-DN-SEED-' . ($index + 100),
                'transaction_date' => Carbon::now()->subDays(rand(1, 30)),
                'currency_id' => $currency->id,
                'subtotal' => 0,
                'net_total' => 0,
                'status' => 'draft',
                'remarks' => 'Seeded Supplier Debit Note',
            ]);

            $subtotal = 0;
            foreach ($products->take(2) as $product) {
                $qty = rand(1, 5);
                $price = $product->price->cost_price_item ?? 50;
                $total = $qty * $price;

                SupplierDebitNoteItem::create([
                    'supplier_debit_note_id' => $sdn->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $total,
                ]);

                $subtotal += $total;
            }

            $sdn->update([
                'subtotal' => $subtotal,
                'net_total' => $subtotal,
            ]);
        }

        // 5. Delivery Orders
        foreach ($customers->take(3) as $index => $customer) {
            $do = DeliveryOrder::create([
                'do_number' => 'DO-SEED-' . ($index + 100),
                'delivery_date' => Carbon::now(),
                'customer_id' => $customer->id,
                'salesperson_id' => $salesperson->id,
                'currency_id' => $currency->id,
                'shipping_address' => $customer->address_line_1,
                'status' => 'draft',
                'remarks' => 'Seeded Delivery Order',
            ]);

            foreach ($products->take(2) as $product) {
                $itemQty = rand(1, 10);
                DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'product_id' => $product->id,
                    'item_qty' => $itemQty,
                    'total_qty_loose' => $itemQty,
                    'item_description' => $product->name,
                ]);
            }
        }
    }
}
