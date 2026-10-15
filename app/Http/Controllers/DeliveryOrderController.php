<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Associate;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Currency;
use App\Models\User;
use App\Models\Quotation;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DeliveryOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deliveryOrders = DeliveryOrder::with('customer', 'salesperson')->latest()->paginate(10);
        return view('sales.delivery_orders.index', compact('deliveryOrders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Associate::withoutGlobalScope('subscriber')->where('type', 'Customer')->where('status', true)->get();
        $currencies = Currency::all(); // BUG-009 FIX: all currencies, not just the default
        // Passing all products for now, similar to other modules. In prod, use AJAX.
        $products = Product::withoutGlobalScope('subscriber')->where('status', true)->with('price', 'packSize')->get();
        $salespeople = User::whereHas('roles', function($q) {
            $q->where('name', 'salesperson');
        })->get();

        $nextId = DeliveryOrder::withoutGlobalScope('subscriber')->max('id') + 1;
        $doNumber = 'DO-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('sales.delivery_orders.create', compact('customers', 'currencies', 'products', 'salespeople', 'doNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->checkStaffPermission('delivery-orders', 'create');
        $request->validate([
            'customer_id' => 'required|exists:associates,id',
            'do_number' => 'required|unique:delivery_orders,do_number',
            'delivery_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
        ]);

        DB::beginTransaction();
        try {
            // BUG-003 FIX: Cache locked inventory objects from validation so Phase 2
            // reuses the same locked rows — no second SELECT, no stale-read window.
            // BUG-022 FIX: Use StockService in Phase 2 so both Product.stock_qty_*
            // and ProductInventory.stock_in_hand are updated consistently.
            $productCache   = [];
            $inventoryCache = [];

            if ($request->action === 'submit') {
                foreach ($request->items as $item) {
                    $product  = Product::find($item['product_id']);
                    $packSize = $product->pack_size_qty ?? 1;
                    $requestedQty = ($item['box_qty'] * $packSize) + $item['item_qty'];

                    $inventory = ProductInventory::where('product_id', $item['product_id'])
                        ->lockForUpdate()->first();

                    if (!$inventory || $inventory->stock_in_hand < $requestedQty) {
                        throw new \Exception(
                            "Insufficient stock for product: {$product->name}. Available: " . ($inventory->stock_in_hand ?? 0)
                        );
                    }

                    $productCache[$item['product_id']]   = $product;
                    $inventoryCache[$item['product_id']] = $inventory;
                }
            }

            $deliveryOrder = DeliveryOrder::create([
                'do_number'      => 'TEMP-' . uniqid('', true), // BUG-005: placeholder, overwritten below
                'delivery_date'  => $request->delivery_date,
                'customer_id'    => $request->customer_id,
                'salesperson_id' => $request->salesperson_id,
                'currency_id'    => $request->currency_id,
                'exchange_rate'  => $request->exchange_rate ?? 1,
                'total_amount'   => $request->total_amount ?? 0,
                'billing_address'  => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'remarks'        => $request->remarks,
                'status'         => $request->action === 'submit' ? 'submitted' : 'draft',
                'quotation_id'   => $request->quotation_id ?? null,
            ]);

            // BUG-005 FIX: generate DO number from the actual auto-increment ID (atomic, no race condition)
            $deliveryOrder->update(['do_number' => 'DO-' . str_pad($deliveryOrder->id, 5, '0', STR_PAD_LEFT)]);

            foreach ($request->items as $item) {
                $product  = $productCache[$item['product_id']] ?? Product::find($item['product_id']);
                $packSize = $product->pack_size_qty ?? 1;
                $totalQtyLoose = ($item['box_qty'] * $packSize) + $item['item_qty'];

                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'product_id'        => $item['product_id'],
                    'box_qty'           => $item['box_qty'] ?? 0,
                    'item_qty'          => $item['item_qty'] ?? 0,
                    'total_qty_loose'   => $totalQtyLoose,
                    'unit_price'        => $item['unit_price'] ?? 0,
                    'tax_rate'          => $item['tax_rate'] ?? 0,
                    'tax_amount'        => $item['tax_amount'] ?? 0,
                    'total_price'       => $item['total_price'] ?? 0,
                    'item_description'  => $item['description'] ?? $product->name,
                ]);

                if ($request->action === 'submit') {
                    StockService::deductStock(
                        $product,
                        (int) ($item['box_qty']  ?? 0),
                        (int) ($item['item_qty'] ?? 0),
                        'Delivery Order',
                        [
                            'do_number'         => $deliveryOrder->do_number,
                            'delivery_order_id' => $deliveryOrder->id,
                            'customer_id'       => $deliveryOrder->customer_id,
                        ]
                    );
                }
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Delivery Order created successfully!', 'redirect' => route('delivery-orders.index')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $deliveryOrder = DeliveryOrder::with('items.product', 'customer', 'salesperson')->findOrFail($id);
        return view('sales.delivery_orders.show', compact('deliveryOrder'));
    }

    /**
     * API: Get Sales Orders (Quotations) for a customer
     */
    public function getCustomerOrders($customerId)
    {
        // BUG-004 FIX: use the actual uppercase status constants used throughout the system
        $orders = Quotation::where('customer_id', $customerId)
                           ->whereIn('status', ['APPROVED_BY_ADMIN', 'APPROVED_BY_SALES', 'WAITING_FOR_SALES_APPROVAL'])
                           ->with('items.product')
                           ->orderBy('created_at', 'desc')
                           ->get();
        return response()->json($orders);
    }
}
