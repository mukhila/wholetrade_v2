<?php

namespace App\Http\Controllers;

use App\Models\SupplierDebitNote;
use App\Models\SupplierDebitNoteItem;
use App\Models\Associate;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierDebitNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $debitNotes = SupplierDebitNote::with('supplier', 'currency')->latest()->paginate(10);
        return view('expenses.debit_notes.index', compact('debitNotes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->get();
        $products = Product::with('price')->where('status', true)->get();
        $currencies = Currency::where('is_default', 1)->get();
        $taxes = Tax::where('status', true)->get();
        
        $nextId = SupplierDebitNote::max('id') + 1;
        $transactionNumber = 'SDN-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('expenses.debit_notes.create', compact('suppliers', 'products', 'currencies', 'taxes', 'transactionNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->checkStaffPermission('debit-notes', 'create');
        $request->validate([
            'supplier_id' => 'required|exists:associates,id',
            'transaction_number' => 'required|unique:supplier_debit_notes,transaction_number',
            'transaction_date' => 'required|date',
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $debitNote = SupplierDebitNote::create([
                'supplier_id' => $request->supplier_id,
                'transaction_number' => $request->transaction_number,
                'transaction_date' => $request->transaction_date,
                'currency_id' => $request->currency_id,
                'exchange_rate' => $request->exchange_rate,
                'tax_id' => $request->tax_id,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'subtotal' => $request->subtotal,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->total_tax_amount ?? 0,
                'round_off' => $request->round_off ?? 0,
                'net_total' => $request->net_total,
                'remarks' => $request->remarks,
                'status' => $request->action === 'submit' ? 'submitted' : 'draft',
            ]);

            foreach ($request->items as $item) {
                SupplierDebitNoteItem::create([
                    'supplier_debit_note_id' => $debitNote->id,
                    'product_id' => $item['product_id'] ?? null,
                    'gl_account' => $item['gl_account'] ?? null,
                    'description' => $item['description'],
                    'narration' => $item['narration'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total_price' => $item['total_price'],
                ]);
            }
            
            if ($debitNote->status === 'submitted') {
                // Accounting Logic: Debit Supplier, Credit Expense
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Debit Note created successfully!', 'redirect' => route('admin.debit-notes.index')]);

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
        $debitNote = SupplierDebitNote::with('items.product', 'supplier', 'currency')->findOrFail($id);
        return view('expenses.debit_notes.show', compact('debitNote'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->checkStaffPermission('debit-notes', 'delete');
        $debitNote = SupplierDebitNote::findOrFail($id);
        if ($debitNote->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Only draft debit notes can be deleted.'], 403);
        }
        $debitNote->delete();
        return response()->json(['success' => true, 'message' => 'Debit Note deleted successfully.']);
    }
}
