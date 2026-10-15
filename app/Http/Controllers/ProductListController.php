<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\CompanySetting;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\ProductBrand;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductListController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
                'price',
                'image'    => fn($q) => $q->withoutGlobalScopes(),
                'category',
                'brand',
                'uom',
                'aliases'  => fn($q) => $q->withoutGlobalScope('subscriber')->where('status', true),
            ])
            ->where('status', true);

        if ($request->filled('search')) {
            $s = $request->search;
            $aliasProductIds = ProductAlias::withoutGlobalScope('subscriber')
                ->where('status', true)
                ->where(fn($q) => $q->where('alias_name', 'like', "%{$s}%")->orWhere('alias_sku', 'like', "%{$s}%"))
                ->pluck('product_id');

            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('sku', 'like', "%{$s}%")
                ->orWhereIn('id', $aliasProductIds));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->boolean('in_stock')) {
            $query->where(function ($q) {
                $q->where('stock_qty_pcs', '>', 0)
                  ->orWhere('stock_qty_box', '>', 0);
            });
        }

        $perPage = in_array($request->get('per_page'), [12, 24, 48, 96]) ? $request->get('per_page') : 24;
        $products = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $categories = ItemCategory::orderBy('name')->get();
        $brands     = ProductBrand::orderBy('name')->get();
        $associates = Associate::where('status', true)->orderBy('type')->orderBy('name')->get();

        return view('product-list.index', compact('products', 'categories', 'brands', 'associates', 'perPage'));
    }

    public function getAssociateDetails($id)
    {
        $associate = Associate::with('currency')->findOrFail($id);

        $address = trim(implode(', ', array_filter([
            $associate->address_line_1,
            $associate->address_line_2,
            $associate->postal_code,
        ])));

        return response()->json([
            'id'              => $associate->id,
            'name'            => $associate->name,
            'code'            => $associate->code,
            'type'            => $associate->type,
            'address'         => $address,
            'phone'           => $associate->phone,
            'email'           => $associate->email,
            'contact_person'  => $associate->contact_person,
            'credit_limit'    => $associate->credit_limit ?? 0,
            'outstanding'     => $associate->getTotalOutstandingBalance(),
            'currency_symbol' => $associate->currency->symbol ?? '$',
        ]);
    }

    public function createInvoice(Request $request)
    {
        $request->validate([
            'associate_id'             => 'required|exists:associates,id',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.qty'              => 'required|numeric|min:0.01',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.description'      => 'nullable|string|max:255',
            'items.*.mode'             => 'nullable|in:piece,box',
        ]);

        DB::beginTransaction();
        try {
            $associate = Associate::findOrFail($request->associate_id);

            $billing = trim(implode(', ', array_filter([
                $associate->address_line_1,
                $associate->address_line_2,
                $associate->postal_code,
            ])));

            $subtotal = 0;
            $lineItems = [];

            foreach ($request->items as $raw) {
                $product   = Product::with(['uom'])->findOrFail($raw['product_id']);
                $mode      = $raw['mode'] ?? 'piece';
                $qty       = (float) $raw['qty'];
                $packSize  = max(1, (int)($product->pack_size_qty ?? 1));
                $boxQty    = $mode === 'box'   ? (int) $qty : 0;
                $pcsQty    = $mode === 'piece' ? (int) $qty : 0;
                $totalQty  = ($boxQty * $packSize) + $pcsQty;
                $lineTotal = round($qty * $raw['unit_price'], 2);
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product'     => $product,
                    'description' => $raw['description'] ?? $product->name,
                    'box_qty'     => $boxQty,
                    'item_qty'    => $pcsQty,
                    'total_qty'   => $totalQty,
                    'unit_price'  => $raw['unit_price'],
                    'total'       => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);

            $invoice = SalesInvoice::create([
                'subscriber_id'   => Auth::user()->subscriber_id ?? null,
                'invoice_number'  => 'DRAFT-' . time(),
                'invoice_date'    => now()->toDateString(),
                'customer_id'     => $associate->id,
                'salesperson_id'  => Auth::id(),
                'billing_address' => $billing,
                'gross_total'     => $subtotal,
                'subtotal'        => $subtotal,
                'discount_amount' => 0,
                'tax_amount'      => 0,
                'round_off'       => 0,
                'net_total'       => $subtotal,
                'paid_amount'     => 0,
                'balance_due'     => $subtotal,
                'payment_status'  => 'unpaid',
                'status'          => 'draft',
                'remarks'         => $request->remarks ?? null,
            ]);

            $invoice->invoice_number = 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT);
            $invoice->save();

            foreach ($lineItems as $li) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id'       => $li['product']->id,
                    'item_description' => $li['description'],
                    'box_qty'          => $li['box_qty'],
                    'item_qty'         => $li['item_qty'],
                    'total_qty'        => $li['total_qty'],
                    'unit_price'       => $li['unit_price'],
                    'discount_amount'  => 0,
                    'tax_rate'         => 0,
                    'tax_amount'       => 0,
                    'total_price'      => $li['total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success'        => true,
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function confirmInvoice($id)
    {
        $invoice = SalesInvoice::with('items.product')->findOrFail($id);

        if (!$invoice->isDraft()) {
            return response()->json(['success' => false, 'message' => 'Invoice is already confirmed.'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($invoice->items as $item) {
                if ($item->product_id && $item->product) {
                    StockService::deductStock(
                        $item->product,
                        (int) $item->box_qty,
                        (int) $item->item_qty,
                        'Product List Invoice',
                        [
                            'invoice_number' => $invoice->invoice_number,
                            'invoice_id'     => $invoice->id,
                            'customer_id'    => $invoice->customer_id,
                        ]
                    );
                }
            }

            $invoice->status = 'confirmed';
            $invoice->save();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Invoice confirmed and stock deducted.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getInvoiceDetails($id)
    {
        $invoice = SalesInvoice::with(['items.product.uom', 'customer', 'currency'])->findOrFail($id);

        $items = $invoice->items->map(fn($item) => [
            'description' => $item->item_description ?: ($item->product->name ?? ''),
            'sku'         => $item->product->sku ?? '',
            'uom'         => $item->product->uom->name ?? '',
            'qty'         => $item->total_qty,
            'unit_price'  => $item->unit_price,
            'total_price' => $item->total_price,
        ]);

        return response()->json([
            'invoice_number'   => $invoice->invoice_number,
            'invoice_date'     => $invoice->invoice_date,
            'status'           => $invoice->status,
            'payment_status'   => $invoice->payment_status,
            'customer_name'    => $invoice->customer->name ?? '',
            'customer_type'    => $invoice->customer->type ?? '',
            'billing_address'  => $invoice->billing_address,
            'subtotal'         => $invoice->subtotal,
            'tax_amount'       => $invoice->tax_amount,
            'discount_amount'  => $invoice->discount_amount,
            'net_total'        => $invoice->net_total,
            'balance_due'      => $invoice->balance_due,
            'currency_symbol'  => $invoice->currency->symbol ?? '$',
            'items'            => $items,
            'can_confirm'      => $invoice->canConfirm(),
        ]);
    }

    public function print($id)
    {
        $invoice                    = SalesInvoice::with(['items.product.uom', 'customer', 'currency'])->findOrFail($id);
        $companySettings            = CompanySetting::first();
        $customerOutstandingBalance = $invoice->customer?->getTotalOutstandingBalance() ?? 0;

        return view('sales.invoices.print', compact('invoice', 'companySettings', 'customerOutstandingBalance'));
    }

    public function thermalPrint($id)
    {
        $invoice                    = SalesInvoice::with(['items.product.uom', 'customer', 'currency'])->findOrFail($id);
        $companySettings            = CompanySetting::first();
        $customerOutstandingBalance = $invoice->customer?->getTotalOutstandingBalance() ?? 0;

        return view('sales.invoices.thermal-print', compact('invoice', 'companySettings', 'customerOutstandingBalance'));
    }
}
