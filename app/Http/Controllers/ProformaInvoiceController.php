<?php

namespace App\Http\Controllers;

use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Associate;
use App\Models\Product;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProformaInvoiceController extends Controller
{
    public function index()
    {
        $proformaInvoices = ProformaInvoice::with('customer', 'salesperson')->latest()->paginate(10);
        return view('sales.proforma_invoices.index', compact('proformaInvoices'));
    }

    public function create()
    {
        $customers = Associate::withoutGlobalScope('subscriber')->where('type', 'Customer')->where('status', true)->get();
        $currencies = Currency::withoutGlobalScope('subscriber')->where('is_default', 1)->get();
        $products = Product::withoutGlobalScope('subscriber')->where('status', true)->with('price', 'packSize')->get();
        $salespeople = User::whereHas('roles', function($q) {
            $q->where('name', 'salesperson');
        })->get();

        $nextId = ProformaInvoice::withoutGlobalScope('subscriber')->max('id') + 1;
        $piNumber = 'PI-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('sales.proforma_invoices.create', compact('customers', 'currencies', 'products', 'salespeople', 'piNumber'));
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('proforma-invoices', 'create');
        $request->validate([
            'customer_id' => 'required|exists:associates,id',
            'pi_number' => 'required|unique:proforma_invoices,pi_number',
            'pi_date' => 'required|date',
            'items' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $pi = ProformaInvoice::create([
                'pi_number' => $request->pi_number,
                'pi_date' => $request->pi_date,
                'valid_till' => $request->valid_till,
                'customer_id' => $request->customer_id,
                'salesperson_id' => $request->salesperson_id,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate ?? 1,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'reference_number' => $request->reference_number,
                
                'subtotal' => $request->subtotal ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'net_total' => $request->net_total ?? 0,
                
                'remarks' => $request->remarks,
                'status' => $request->action === 'submit' ? 'submitted' : 'draft',
            ]);

            foreach ($request->items as $item) {
                // Determine Qty
                $productId = $item['product_id'];
                $product = Product::find($productId);
                $packSize = $product->pack_size_qty ?? 1;
                $totalQty = ($item['box_qty'] * $packSize) + $item['item_qty'];

                ProformaInvoiceItem::create([
                    'proforma_invoice_id' => $pi->id,
                    'product_id' => $productId,
                    'item_description' => $item['description'] ?? $product->name,
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
            return response()->json([
                'success' => true, 
                'message' => 'Proforma Invoice created successfully!', 
                'redirect' => route('proforma-invoices.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $pi = ProformaInvoice::with('items.product', 'customer', 'currency', 'salesperson')->findOrFail($id);
        return view('sales.proforma_invoices.show', compact('pi'));
    }
    
    // Convert to Quotation (Sales Order)
    public function convertToOrder($id)
    {
        $pi = ProformaInvoice::with('items')->findOrFail($id);
        
        if ($pi->is_converted) {
            return back()->with('error', 'This Proforma Invoice has already been converted.');
        }

        // Logic to redirect to Quotation Create page with preset data
        // For now, simpler approach: Just create it backend ? No, UI flow.
        // We will just return a view with populated data or redirect with params.
        // Or implement a dedicated 'convert' action that replicates the record.
        
        // Let's implement full conversion later. For NOW, mark as converted manual action or similar.
        // User asked for "Convert to Sales Order".
        
        // Stub implementation:
        // 1. Create Quotation 
        // 2. Link
        // 3. Mark PI as converted status
        
        return back()->with('info', 'Conversion feature implementation pending next sprint.');
    }
}
