<?php

namespace App\Http\Controllers;

use App\Models\SalesCreditNote;
use App\Models\SalesCreditNoteItem;
use App\Models\SalesInvoice;
use App\Models\Associate;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Tax;
use App\Models\User;
use App\Models\ProductInventory;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;

class SalesCreditNoteController extends Controller
{
    public function index()
    {
        $creditNotes = SalesCreditNote::with('customer', 'currency', 'salesperson', 'invoice')
            ->latest()->paginate(10);
        return view('sales.credit_notes.index', compact('creditNotes'));
    }

    public function create()
    {
        $customers   = Associate::where('type', 'Customer')->where('status', true)->get();
        $products    = Product::where('status', true)->with('price', 'packSize')->get();
        $currencies  = Currency::all();
        $taxes       = Tax::where('status', true)->get();
        $salespeople = User::whereHas('roles', function ($q) {
            $q->where('name', 'salesperson');
        })->get();

        $nextId = SalesCreditNote::max('id') + 1;
        $transactionNumber = 'CN-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('sales.credit_notes.create', compact(
            'customers', 'products', 'currencies', 'taxes', 'salespeople', 'transactionNumber'
        ));
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('sales-credit-notes', 'create');

        $request->validate([
            'customer_id'      => 'required|exists:associates,id',
            'transaction_date' => 'required|date',
            'currency_id'      => 'required|exists:currencies,id',
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
        ]);

        // Validate return quantities do not exceed original invoice quantities
        if ($request->invoice_id) {
            $invoice = SalesInvoice::with('items')->find($request->invoice_id);
            if ($invoice) {
                foreach ($request->items as $returnItem) {
                    $originalItem = $invoice->items
                        ->where('product_id', $returnItem['product_id'])
                        ->first();

                    if ($originalItem) {
                        $returnQty = ($returnItem['box_qty'] ?? 0) * ($returnItem['pack_size'] ?? 1)
                            + ($returnItem['item_qty'] ?? 0);
                        if ($returnQty > $originalItem->total_qty) {
                            return response()->json([
                                'success' => false,
                                'error'   => "Return qty for \"{$originalItem->item_description}\" cannot exceed "
                                           . "the original sold qty of {$originalItem->total_qty}.",
                            ], 422);
                        }
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $isSubmitted = $request->action === 'submit';

            $creditNote = SalesCreditNote::create([
                'customer_id'          => $request->customer_id,
                'salesperson_id'       => $request->salesperson_id,
                'invoice_id'           => $request->invoice_id ?: null,
                'transaction_number'   => 'TEMP-' . uniqid('', true),
                'transaction_date'     => $request->transaction_date,
                'currency_id'          => $request->currency_id,
                'exchange_rate'        => $request->exchange_rate ?? 1,
                'billing_address'      => $request->billing_address,
                'shipping_address'     => $request->shipping_address,
                'subtotal'             => $request->subtotal ?? 0,
                'discount_percentage'  => $request->discount_percentage ?? 0,
                'discount_amount'      => $request->discount_amount ?? 0,
                'tax_amount'           => $request->total_tax_amount ?? 0,
                'round_off'            => $request->round_off ?? 0,
                'grand_total'          => $request->net_total ?? 0,
                'remarks'              => $request->remarks,
                'status'               => $isSubmitted ? 'submitted' : 'draft',
            ]);

            // Canonical number from auto-increment ID
            $creditNote->update([
                'transaction_number' => 'CN-' . str_pad($creditNote->id, 5, '0', STR_PAD_LEFT),
            ]);

            foreach ($request->items as $item) {
                SalesCreditNoteItem::create([
                    'sales_credit_note_id' => $creditNote->id,
                    'product_id'           => $item['product_id'],
                    'description'          => $item['description'] ?? '',
                    'box_qty'              => $item['box_qty'] ?? 0,
                    'item_qty'             => $item['item_qty'] ?? 0,
                    'box_price'            => $item['box_price'] ?? 0,
                    'unit_price'           => $item['unit_price'] ?? 0,
                    'tax_rate'             => $item['tax_rate'] ?? 0,
                    'tax_amount'           => $item['tax_amount'] ?? 0,
                    'total_price'          => $item['total_price'] ?? 0,
                ]);

                // Restore stock when submitted
                if ($isSubmitted) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        StockService::addStock(
                            $product,
                            (int) ($item['box_qty'] ?? 0),
                            (int) ($item['item_qty'] ?? 0),
                            'Sales Return (Credit Note)',
                            [
                                'credit_note_number' => $creditNote->transaction_number,
                                'credit_note_id'     => $creditNote->id,
                                'customer_id'        => $creditNote->customer_id,
                            ]
                        );
                    }
                }
            }

            // Reduce the original invoice's outstanding balance
            if ($isSubmitted && $creditNote->invoice_id) {
                $invoice = SalesInvoice::find($creditNote->invoice_id);
                if ($invoice && in_array($invoice->status, ['confirmed', 'posted'])) {
                    $creditAmount        = floatval($creditNote->grand_total);
                    $invoice->balance_due = max(0, $invoice->balance_due - $creditAmount);

                    if ($invoice->balance_due <= 0.01) {
                        $invoice->balance_due   = 0;
                        $invoice->payment_status = 'paid';
                    }
                    $invoice->save();

                    AuditLog::log(
                        AuditLog::ACTION_PAYMENT,
                        AuditLog::MODULE_SALES_INVOICE,
                        $invoice,
                        [
                            'reference_code' => $invoice->invoice_number,
                            'description'    => "Credit note {$creditNote->transaction_number} applied — balance reduced by {$creditAmount}",
                            'metadata'       => [
                                'credit_note_id'     => $creditNote->id,
                                'credit_note_number' => $creditNote->transaction_number,
                                'credit_amount'      => $creditAmount,
                                'new_balance'        => $invoice->balance_due,
                            ],
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Credit Note created successfully!',
                'redirect' => route('credit-notes.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $creditNote = SalesCreditNote::with('items.product', 'customer', 'currency', 'salesperson', 'invoice')
            ->findOrFail($id);
        return view('sales.credit_notes.show', compact('creditNote'));
    }

    // ──────────────────────────────────────────────────────────────
    //  API helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Return all confirmed/posted invoices for a customer (for the return-against dropdown).
     */
    public function getCustomerInvoices($customerId)
    {
        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->whereIn('status', ['confirmed', 'posted'])
            ->orderBy('invoice_date', 'desc')
            ->get(['id', 'invoice_number', 'invoice_date', 'net_total', 'balance_due', 'payment_status']);

        return response()->json(['success' => true, 'invoices' => $invoices]);
    }

    /**
     * Return the items of a specific invoice so the form can pre-fill return rows.
     */
    public function getInvoiceItems($invoiceId)
    {
        $invoice = SalesInvoice::with(['items.product.price', 'items.product.packSize'])
            ->findOrFail($invoiceId);

        $items = $invoice->items->map(function ($item) {
            $packSize  = (int) ($item->product?->pack_size_qty ?? 1);
            $unitPrice = (float) $item->unit_price;
            return [
                'product_id'        => $item->product_id,
                'product_name'      => $item->product?->name ?? $item->item_description,
                'product_sku'       => $item->product?->sku ?? '',
                'description'       => $item->item_description,
                'original_box_qty'  => (int) $item->box_qty,
                'original_item_qty' => (int) $item->item_qty,
                'original_total_qty'=> (float) $item->total_qty,
                'unit_price'        => $unitPrice,
                // SalesInvoiceItem has no box_price column; derive it from unit_price × pack_size
                'box_price'         => $unitPrice * $packSize,
                'tax_rate'          => (float) $item->tax_rate,
                'pack_size'         => $packSize,
            ];
        });

        return response()->json([
            'success' => true,
            'invoice' => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date'   => $invoice->invoice_date,
                'net_total'      => $invoice->net_total,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Legacy: last credit from old InvoiceReference table (kept for backward compat).
     */
    public function getCustomerLastCredit($customerId)
    {
        // First try the new SalesInvoice system
        $newBalance = \App\Models\Associate::find($customerId)?->getTotalOutstandingBalance() ?? 0;

        return response()->json([
            'success'     => true,
            'balance'     => $newBalance,
            'invoice_no'  => null,
            'net_total'   => 0,
            'paid'        => 0,
            'date'        => null,
        ]);
    }

    public function getProductStock($productId)
    {
        $product = Product::with(['price', 'inventory', 'packSize'])->find($productId);

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => [
                    'id'               => $product->id,
                    'sku'              => $product->sku,
                    'name'             => $product->name,
                    'stock_qty_pcs'    => $product->stock_qty_pcs ?? 0,
                    'stock_qty_box'    => $product->stock_qty_box ?? 0,
                    'stock_in_hand'    => $product->inventory?->stock_in_hand ?? 0,
                    'pack_size_qty'    => $product->pack_size_qty ?? 1,
                    'cost_price_item'  => $product->price?->cost_price_item ?? 0,
                    'cost_price_box'   => $product->price?->cost_price_box ?? 0,
                    'sales_price_item' => $product->price?->sales_price_item ?? 0,
                    'sales_price_box'  => $product->price?->sales_price_box ?? 0,
                    'tax_rate'         => $product->price?->tax_rate ?? 0,
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Product not found']);
    }
}
