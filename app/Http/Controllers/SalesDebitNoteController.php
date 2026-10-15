<?php

namespace App\Http\Controllers;

use App\Models\SalesDebitNote;
use App\Models\SalesDebitNoteItem;
use App\Models\Associate;
use App\Models\Currency;
use App\Models\Tax;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalesDebitNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $debitNotes = SalesDebitNote::with('customer', 'currency', 'salesperson')->latest()->paginate(10);
        return view('sales.debit_notes.index', compact('debitNotes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Associate::where('type', 'Customer')->where('status', true)->get();
        $currencies = Currency::where('is_default', 1)->get();
        $taxes = Tax::where('status', true)->get();
        $salespeople = User::whereHas('roles', function($q) {
            $q->where('name', 'salesperson');
        })->get();
        
        $products = Product::withoutGlobalScope('subscriber')->where('status', true)->select('id', 'name', 'sku')->get();

        $nextId = SalesDebitNote::max('id') + 1;
        $transactionNumber = 'SDN-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('sales.debit_notes.create', compact('customers', 'currencies', 'taxes', 'salespeople', 'products', 'transactionNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->checkStaffPermission('sales-debit-notes', 'create');
        $request->validate([
            'customer_id' => 'required|exists:associates,id',
            'transaction_number' => 'required|unique:sales_debit_notes,transaction_number',
            'transaction_date' => 'required|date',
            'currency_id' => 'required|exists:currencies,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.unit_price' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $debitNote = SalesDebitNote::create([
                'customer_id' => $request->customer_id,
                'salesperson_id' => $request->salesperson_id,
                'invoice_id' => $request->invoice_id,
                'transaction_number' => $request->transaction_number,
                'transaction_date' => $request->transaction_date,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate ?? 1,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'subtotal' => $request->subtotal,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->total_tax_amount ?? 0,
                'round_off' => $request->round_off ?? 0,
                'grand_total' => $request->net_total,
                'remarks' => $request->remarks,
                'status' => $request->action === 'submit' ? 'submitted' : 'draft',
            ]);

            foreach ($request->items as $item) {
                SalesDebitNoteItem::create([
                    'sales_debit_note_id' => $debitNote->id,
                    'gl_account' => $item['gl_account'] ?? null,
                    'description' => $item['description'],
                    'narration' => $item['narration'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total_price' => $item['total_price'],
                ]);
            }
            
            DB::commit();
            
            $redirect = route('debit-notes.index'); 

            return response()->json(['success' => true, 'message' => 'Debit Note created successfully!', 'redirect' => $redirect]);

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
        $debitNote = SalesDebitNote::with('items', 'customer', 'currency', 'salesperson')->findOrFail($id);
        return view('sales.debit_notes.show', compact('debitNote'));
    }
}
