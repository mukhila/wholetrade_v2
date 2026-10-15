<?php

namespace App\Http\Controllers;

use App\Models\PurchaseDeliveryNote;
use App\Models\PurchaseDeliveryNoteItem;
use App\Models\Associate;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Currency;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseDeliveryNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deliveryNotes = PurchaseDeliveryNote::with('supplier', 'receiver')->latest()->paginate(10);
        return view('expenses.purchase_delivery_notes.index', compact('deliveryNotes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->get();
        $currencies = Currency::where('is_default', 1)->get();
        $products = Product::where('status', true)->with('price', 'packSize')->get();
        $receivers = User::all(); // Anyone can receive
        
        $nextId = PurchaseDeliveryNote::max('id') + 1;
        $doNumber = 'PDN-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('expenses.purchase_delivery_notes.create', compact('suppliers', 'currencies', 'products', 'receivers', 'doNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->checkStaffPermission('purchase-delivery-notes', 'create');
        $request->validate([
            'supplier_id' => 'required|exists:associates,id',
            'do_number' => 'required|unique:purchase_delivery_notes,do_number',
            'delivery_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
        ]);

        DB::beginTransaction();
        try {
            $pdn = PurchaseDeliveryNote::create([
                'do_number' => $request->do_number,
                'delivery_date' => $request->delivery_date,
                'supplier_id' => $request->supplier_id,
                'receiver_id' => $request->receiver_id ?? Auth::id(), // Default to logged in user
                'currency_id' => $request->currency_id,
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address,
                'supplier_do_reference' => $request->supplier_do_reference,
                'remarks' => $request->remarks,
                'status' => $request->action === 'submit' ? 'submitted' : 'draft',
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $packSize = $product->pack_size_qty ?? 1;
                $totalQtyLoose = ($item['box_qty'] * $packSize) + $item['item_qty'];
                
                PurchaseDeliveryNoteItem::create([
                    'purchase_delivery_note_id' => $pdn->id,
                    'product_id' => $item['product_id'],
                    'box_qty' => $item['box_qty'] ?? 0,
                    'item_qty' => $item['item_qty'] ?? 0,
                    'total_qty_loose' => $totalQtyLoose,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total_price' => $item['total_price'] ?? 0,
                    'item_description' => $item['description'] ?? $product->name,
                ]);
                
                // INCREMENT Stock on Submit (Inbound) with audit logging
                if ($request->action === 'submit') {
                    StockService::addStock(
                        $product,
                        $item['box_qty'] ?? 0,
                        $item['item_qty'] ?? 0,
                        'Purchase Delivery Note',
                        [
                            'do_number' => $pdn->do_number,
                            'supplier_id' => $request->supplier_id,
                            'purchase_delivery_note_id' => $pdn->id,
                        ]
                    );
                }
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Purchase Delivery Note created successfully!', 'redirect' => route('purchase-delivery-notes.index')]);

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
        $deliveryNote = PurchaseDeliveryNote::with('items.product', 'supplier', 'receiver')->findOrFail($id);
        return view('expenses.purchase_delivery_notes.show', compact('deliveryNote'));
    }
}
