<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Associate;
use App\Models\Product;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('supplier')->latest()->paginate(10);
        return view('expenses.purchase_orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Associate::withoutGlobalScope('subscriber')->where('type', 'Supplier')->where('status', true)->get(['id', 'name', 'code', 'address_line_1', 'address_line_2', 'currency_id', 'gst_option']);
        $currencies = Currency::all(); // BUG-009 FIX: all currencies, not just the default
        $products = Product::withoutGlobalScope('subscriber')->where('status', true)->with('price', 'packSize')->get();

        // BUG-005: preview only; actual number generated from DB ID in store()
        $nextId = PurchaseOrder::withoutGlobalScope('subscriber')->max('id') + 1;
        $poNumber = 'PO-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('expenses.purchase_orders.create', compact('suppliers', 'currencies', 'products', 'poNumber'));
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('purchase-orders', 'create');
        // BUG-005 FIX: po_number is auto-generated from DB ID; removed from validation.
        $request->validate([
            'supplier_id' => 'required|exists:associates,id',
            'po_date' => 'required|date',
            'items' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $po = PurchaseOrder::create([
                'po_number' => 'TEMP-' . uniqid('', true),
                'po_date' => $request->po_date,
                'supplier_id' => $request->supplier_id,
                'supplier_ref_no' => $request->supplier_ref_no,
                'expected_shipping_date' => $request->expected_shipping_date,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate ?? 1,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'subtotal' => $request->subtotal ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'net_total' => $request->net_total ?? 0,
                'remarks' => $request->remarks,
                'status'  => $request->action === 'submit' ? 'submitted' : 'draft',
            ]);

            // BUG-005 FIX: set canonical number from auto-increment ID
            $po->update(['po_number' => 'PO-' . str_pad($po->id, 5, '0', STR_PAD_LEFT)]);

            foreach ($request->items as $item) {
                // Determine Type: Product or Service
                $productId = $item['product_id'] ?? null;
                $glAccount = $item['gl_account'] ?? null;
                
                // Calculate Total Units if product
                $totalQty = 0;
                if ($productId) {
                    $product = Product::find($productId);
                    $packSize = $product->pack_size_qty ?? 1;
                    $totalQty = ($item['box_qty'] * $packSize) + $item['item_qty'];
                } else {
                     // If service, usually just 1 unit or qty provided
                     $totalQty = $item['quantity'] ?? 1;
                }

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $productId,
                    'gl_account' => $glAccount,
                    'description' => $item['description'] ?? '',
                    'narration' => $item['narration'] ?? null,
                    'box_qty' => $item['box_qty'] ?? 0,
                    'item_qty' => $item['item_qty'] ?? 0,
                    'total_qty' => $totalQty,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total_price' => $item['total_price'] ?? 0,
                ]);
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Purchase Order created successfully!', 'redirect' => route('purchase-orders.index')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with('items.product', 'supplier', 'currency')->findOrFail($id);
        return view('expenses.purchase_orders.show', compact('purchaseOrder'));
    }
}
