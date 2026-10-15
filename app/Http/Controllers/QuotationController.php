<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Associate;
use App\Models\ItemCategory;
use App\Models\ProductBrand;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Notifications\QuotationStatusUpdated;
use App\Models\User;

class QuotationController extends Controller
{
    // Blade: Dashboard (List Quotations)
    public function index()
    {
        // BUG-012 FIX: removed ?? 1 fallback; routes are auth-protected
        $quotations = Quotation::with('customer:id,name')
            ->where('salesperson_id', Auth::id())
            ->latest()
            ->paginate(10);
            
        return view('quotations.index', compact('quotations'));
    }

    // Blade: Create Quotation Screen
    public function create()
    {
        $categories = ItemCategory::withoutGlobalScope('subscriber')->where('status', true)->get(['id', 'name']);
        $brands = ProductBrand::withoutGlobalScope('subscriber')->where('status', true)->get(['id', 'name']);
        $customers = Associate::withoutGlobalScope('subscriber')->where('type', 'Customer')->where('status', true)->get(['id', 'name', 'code']);

        return view('quotations.create', compact('categories', 'brands', 'customers'));
    }

    // API: Get Products with filters (Used by JS in Create View)
    public function getProducts(Request $request)
    {
        $query = Product::withoutGlobalScope('subscriber')
            ->with(['price', 'category:id,name', 'brand:id,name'])
            ->where('status', true);

        if ($request->has('categoryId') && $request->categoryId) {
            $query->where('category_id', $request->categoryId);
        }

        if ($request->has('brandId') && $request->brandId) {
            $query->where('brand_id', $request->brandId);
        }

        $aliasMatchedIds = collect();
        $aliasMap = collect(); // product_id => [alias records]

        if ($request->has('search') && $request->search) {
            $search = $request->search;

            // Find products whose aliases match the search
            $aliases = ProductAlias::withoutGlobalScope('subscriber')
                ->where('status', true)
                ->where(function ($q) use ($search) {
                    $q->where('alias_name', 'like', '%' . $search . '%')
                      ->orWhere('alias_sku', 'like', '%' . $search . '%');
                })
                ->get();

            $aliasMatchedIds = $aliases->pluck('product_id');
            $aliasMap = $aliases->groupBy('product_id');

            $query->where(function ($q) use ($search, $aliasMatchedIds) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhereIn('id', $aliasMatchedIds);
            });
        }

        $products = $query->paginate(20);

        // Transform collection to include resolved price and any matched aliases
        $products->getCollection()->transform(function ($product) use ($aliasMap) {
            $matchedAliases = $aliasMap->get($product->id, collect());
            $aliases = $matchedAliases->map(fn($a) => [
                'id'         => $a->id,
                'alias_name' => $a->alias_name,
                'alias_sku'  => $a->alias_sku,
            ])->values()->toArray();

            return [
                'id'       => $product->id,
                'sku'      => $product->sku,
                'name'     => $product->name,
                'category' => $product->category->name ?? '',
                'brand'    => $product->brand->name ?? '',
                'image'    => $product->product_mainimage ?? 'assets/images/products/img-1.png',
                'stock'    => max(0, ($product->stock_qty_box ?? 0) * ($product->pack_size_qty ?? 1) + ($product->stock_qty_pcs ?? 0)),
                'stock_box' => $product->stock_qty_box ?? 0,
                'stock_pcs' => $product->stock_qty_pcs ?? 0,
                'prices' => [
                    'unit_price' => $product->price?->sales_price_item ?? 0,
                    'box_price'  => $product->price?->sales_price_box ?? 0,
                    'tax_rate'   => $product->price?->tax_rate ?? 0,
                ],
                'aliases' => $aliases,
            ];
        });

        return response()->json($products);
    }

    // Blade: Checkout Page - displayed after "Proceed to Checkout" from Cart
    public function checkout()
    {
        $cart = session()->get('cart', []);
        if(empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Cart is empty');
        }

        $customers = Associate::withoutGlobalScope('subscriber')->where('type', 'Customer')->where('status', true)->get(['id', 'name', 'code']);
        
        // Calculate Totals for Display
        $grandTotal = 0;
        foreach($cart as $item) {
             $grandTotal += ($item['item_qty'] * $item['unit_price']) + ($item['box_qty'] * $item['box_price']);
        }

        return view('quotations.checkout', compact('cart', 'customers', 'grandTotal'));
    }

    // API: Submit Quotation from Checkout Page
    public function store(Request $request)
    {
        $this->checkStaffPermission('quotations', 'create');
        $request->validate([
            'customer_id' => 'required|exists:associates,id',
            'remarks' => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:1',
        ]);

        $cart = [];
        $isApiPayload = false;

        // 1. Determine Source
        if ($request->has('items') && !empty($request->items)) {
            // Salesperson Flow (JS Payload)
            $cart = $request->items;
            $isApiPayload = true;
        } else {
            // Checkout Flow (Session Cart)
            $cart = session()->get('cart', []);
        }

        if (empty($cart)) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $taxAmount = 0;

            // 2. Calculate Totals based on source structure
            if ($isApiPayload) {
                foreach ($cart as $item) {
                    // BUG-014 FIX: total_price is tax-exclusive; adding tax separately gives the correct grand total.
                    // The previous code subtracted tax from total_price (wrong) before re-adding it (accidental correct total
                    // but wrong stored subtotal). Now subtotal = sum of pre-tax line totals.
                    $lineSubtotal = $item['total_price'] ?? ($item['quantity'] * ($item['unit_price'] ?? 0));
                    $lineTax      = $item['tax_amount'] ?? 0;
                    $subtotal    += $lineSubtotal;
                    $taxAmount   += $lineTax;
                }
            } else {
                foreach ($cart as $item) {
                    $subtotal += ($item['item_qty'] * $item['unit_price']) + ($item['box_qty'] * $item['box_price']);
                }
            }

            $grandTotal = $subtotal + $taxAmount;

            $quotation = Quotation::create([
                'salesperson_id' => Auth::id(), // BUG-012 FIX: removed ?? 1 fallback
                'customer_id'   => $request->customer_id,
                'status' => 'PENDING_ADMIN_APPROVAL',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'remarks' => $request->remarks,
            ]);

            // 3. Create Items
            foreach ($cart as $item) {
                if ($isApiPayload) {
                    // Map JS payload to DB columns
                    $isBox = ($item['unit_type'] === 'Box');

                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'product_id' => $item['product_id'],
                        'item_qty' => $isBox ? 0 : $item['quantity'],
                        'box_qty' => $isBox ? $item['quantity'] : 0,
                        'unit_price' => $isBox ? 0 : $item['unit_price'],
                        'box_price' => $isBox ? $item['unit_price'] : 0,
                        'tax_rate' => $item['tax_rate'] ?? 0,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'total_price' => $item['total_price'],
                    ]);
                } else {
                    // Session Cart Structure
                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'product_id' => $item['id'],
                        'item_qty' => $item['item_qty'],
                        'box_qty' => $item['box_qty'],
                        'unit_price' => $item['unit_price'],
                        'box_price' => $item['box_price'],
                        'tax_rate' => $item['tax_rate'] ?? 0,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'total_price' => ($item['item_qty'] * $item['unit_price']) + ($item['box_qty'] * $item['box_price']),
                    ]);
                }
            }

            // Clear Cart only if using session
            if (!$isApiPayload) {
                session()->forget('cart');
            }

            DB::commit();
            return response()->json([
                'message' => 'Quotation submitted successfully', 
                'quotation_number' => $quotation->quotation_number,
                'redirect' => route('quotations.index') 
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Blade: Show Quotation Details (Used for Revision Review)
    public function show($id)
    {
        $quotation = Quotation::with(['items.product', 'customer'])->findOrFail($id);
        
        // Ensure user owns this quotation
        if ($quotation->salesperson_id != (Auth::id() ?? 1) && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        return view('quotations.show', compact('quotation'));
    }

    // API: Accept Revision
    public function acceptRevision($id)
    {
        DB::beginTransaction();
        try {
            $quotation = Quotation::findOrFail($id);
            
            if ($quotation->status !== 'WAITING_FOR_SALES_APPROVAL') {
                return response()->json(['error' => 'Invalid status.'], 400);
            }

            $quotation->status = 'APPROVED_BY_SALES';
            $quotation->save();

            $invoice = \App\Models\Invoice::create([
                'quotation_id'   => $quotation->id,
                'subtotal'       => $quotation->subtotal,
                'tax_amount'     => $quotation->tax_amount,
                'grand_total'    => $quotation->grand_total,
                'payment_status' => 'Pending',
            ]);

            // BUG-013 FIX: notify by role, not hardcoded User ID 1
            $admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();
            if ($admin) {
                $admin->notify(new QuotationStatusUpdated($quotation, 'APPROVED', "Revision accepted by {$quotation->salesperson->name}. Invoice generated."));
            }

            DB::commit();
            return response()->json(['message' => 'Revision accepted. Invoice generated.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // API: Reject Revision
    public function rejectRevision(Request $request, $id)
    {
        $quotation = Quotation::with('salesperson')->findOrFail($id);
        
        if ($quotation->status !== 'WAITING_FOR_SALES_APPROVAL') {
            return response()->json(['error' => 'Invalid status.'], 400);
        }

        $quotation->status  = 'REJECTED_BY_SALES';
        $quotation->remarks = $request->reason ?? 'Salesperson rejected the revision.';
        $quotation->save();

        // BUG-013 FIX: notify by role, not hardcoded User ID 1
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();
        if ($admin) {
            $admin->notify(new QuotationStatusUpdated($quotation, 'REJECTED_BY_SALES', "Revision REJECTED by {$quotation->salesperson->name}. Reason: " . $quotation->remarks));
        }

        return response()->json(['message' => 'Revision rejected. Quotation closed.']);
    }
}
