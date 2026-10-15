<?php

namespace App\Http\Controllers;

use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use App\Models\Associate;
use App\Models\Product;
use App\Models\Currency;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupplierInvoiceController extends Controller
{
    public function index()
    {
        $invoices = SupplierInvoice::with('supplier', 'currency')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $counts = [
            'all'      => SupplierInvoice::count(),
            'draft'    => SupplierInvoice::where('status', 'draft')->count(),
            'confirmed'=> SupplierInvoice::where('status', 'confirmed')->count(),
            'unpaid'   => SupplierInvoice::where('payment_status', 'unpaid')->where('status', 'confirmed')->count(),
            'partial'  => SupplierInvoice::where('payment_status', 'partial')->count(),
            'paid'     => SupplierInvoice::where('payment_status', 'paid')->count(),
        ];

        return view('supplier_invoices.index', compact('invoices', 'counts'));
    }

    public function create()
    {
        $suppliers  = Associate::where('type', 'Supplier')->where('status', true)->get();
        $currencies = Currency::all(); // BUG-009 FIX
        $products   = Product::where('status', true)->with('price', 'packSize', 'uom')->get();

        // BUG-005: preview only; actual number generated from DB ID in store()
        $nextId    = SupplierInvoice::withTrashed()->max('id') + 1;
        $invNumber = 'SINV-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('supplier_invoices.create', compact('suppliers', 'currencies', 'products', 'invNumber'));
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('supplier-invoices', 'create');
        // BUG-005 FIX: invoice_number auto-generated from DB ID; removed from validation.
        $request->validate([
            'supplier_id'        => 'required|exists:associates,id',
            'invoice_date'       => 'required|date',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
        ]);

        DB::beginTransaction();
        try {
            $invoice = SupplierInvoice::create([
                'invoice_number'      => 'TEMP-' . uniqid('', true),
                'invoice_date'        => $request->invoice_date,
                'due_date'            => $request->due_date,
                'supplier_id'         => $request->supplier_id,
                'currency_id'         => $request->currency_id,
                'exchange_rate'       => $request->exchange_rate ?? 1,
                'supplier_ref_number' => $request->supplier_ref_number,
                'billing_address'     => $request->billing_address,
                'remarks'             => $request->remarks,
                'subtotal'            => $request->subtotal ?? 0,
                'discount_amount'     => $request->discount_amount ?? 0,
                'tax_amount'          => $request->tax_amount ?? 0,
                'net_total'           => $request->net_total ?? 0,
                'paid_amount'    => 0,
                'balance_due'   => $request->net_total ?? 0,
                'payment_status'=> 'unpaid',
                'status'        => 'draft',
            ]);

            // BUG-005 FIX: overwrite temp placeholder with ID-based canonical number
            $invoice->update(['invoice_number' => 'SINV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT)]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $packSize = $product->pack_size_qty ?? 1;
                $totalQty = (($item['box_qty'] ?? 0) * $packSize) + ($item['item_qty'] ?? 0);

                SupplierInvoiceItem::create([
                    'supplier_invoice_id' => $invoice->id,
                    'product_id'          => $item['product_id'],
                    'item_description'    => $item['description'] ?? $product->name,
                    'box_qty'             => $item['box_qty'] ?? 0,
                    'item_qty'            => $item['item_qty'] ?? 0,
                    'total_qty'           => $totalQty,
                    'unit_price'          => $item['unit_price'] ?? 0,
                    'discount_amount'     => $item['discount_amount'] ?? 0,
                    'tax_rate'            => $item['tax_rate'] ?? 0,
                    'tax_amount'          => $item['tax_amount'] ?? 0,
                    'total_price'         => $item['total_price'] ?? 0,
                ]);
            }

            DB::commit();
            return response()->json([
                'success'  => true,
                'message'  => 'Supplier Invoice saved as draft.',
                'redirect' => route('supplier-invoices.show', $invoice->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $invoice = SupplierInvoice::with(['items.product.uom', 'items.product.packSize', 'supplier', 'currency'])
            ->findOrFail($id);

        $paymentHistory = DB::table('supplier_invoice_payments')
            ->where('supplier_invoice_id', $id)
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('supplier_invoices.show', compact('invoice', 'paymentHistory'));
    }

    public function edit($id)
    {
        $invoice = SupplierInvoice::with('items.product')->findOrFail($id);

        if (!$invoice->canEdit()) {
            return redirect()->route('supplier-invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $suppliers  = Associate::where('type', 'Supplier')->where('status', true)->get();
        $currencies = Currency::all(); // BUG-009 FIX
        $products   = Product::where('status', true)->with('price', 'packSize', 'uom')->get();

        return view('supplier_invoices.edit', compact('invoice', 'suppliers', 'currencies', 'products'));
    }

    public function update(Request $request, $id)
    {
        $this->checkStaffPermission('supplier-invoices', 'edit');
        $invoice = SupplierInvoice::findOrFail($id);

        if (!$invoice->canEdit()) {
            return response()->json(['success' => false, 'error' => 'Only draft invoices can be edited.'], 422);
        }

        $request->validate([
            'supplier_id'       => 'required|exists:associates,id',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
        ]);

        DB::beginTransaction();
        try {
            $newNetTotal = $request->net_total ?? 0;
            // BUG-016 FIX: balance_due must reflect payments already recorded.
            $newBalanceDue = max(0, $newNetTotal - $invoice->paid_amount);

            $invoice->update([
                'invoice_date'        => $request->invoice_date,
                'due_date'            => $request->due_date,
                'supplier_id'         => $request->supplier_id,
                'currency_id'         => $request->currency_id,
                'supplier_ref_number' => $request->supplier_ref_number,
                'billing_address'     => $request->billing_address,
                'remarks'             => $request->remarks,
                'subtotal'            => $request->subtotal ?? 0,
                'discount_amount'     => $request->discount_amount ?? 0,
                'tax_amount'          => $request->tax_amount ?? 0,
                'net_total'           => $newNetTotal,
                'balance_due'         => $newBalanceDue,
            ]);

            $invoice->items()->delete();

            foreach ($request->items as $item) {
                $product  = Product::find($item['product_id']);
                $packSize = $product->pack_size_qty ?? 1;
                $totalQty = (($item['box_qty'] ?? 0) * $packSize) + ($item['item_qty'] ?? 0);

                SupplierInvoiceItem::create([
                    'supplier_invoice_id' => $invoice->id,
                    'product_id'          => $item['product_id'],
                    'item_description'    => $item['description'] ?? $product->name,
                    'box_qty'             => $item['box_qty'] ?? 0,
                    'item_qty'            => $item['item_qty'] ?? 0,
                    'total_qty'           => $totalQty,
                    'unit_price'          => $item['unit_price'] ?? 0,
                    'discount_amount'     => $item['discount_amount'] ?? 0,
                    'tax_rate'            => $item['tax_rate'] ?? 0,
                    'tax_amount'          => $item['tax_amount'] ?? 0,
                    'total_price'         => $item['total_price'] ?? 0,
                ]);
            }

            DB::commit();
            return response()->json([
                'success'  => true,
                'message'  => 'Invoice updated successfully.',
                'redirect' => route('supplier-invoices.show', $invoice->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Confirm the invoice → add stock for all items
     */
    public function confirm($id)
    {
        $this->checkStaffPermission('supplier-invoices', 'edit');
        $invoice = SupplierInvoice::with('items.product')->findOrFail($id);

        if (!$invoice->canConfirm()) {
            return response()->json(['success' => false, 'error' => 'Invoice cannot be confirmed.'], 422);
        }

        if ($invoice->stock_added) {
            return response()->json(['success' => false, 'error' => 'Stock has already been added for this invoice.'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($invoice->items as $item) {
                $product = $item->product;
                if (!$product) continue;

                StockService::addStock(
                    $product,
                    (int) $item->box_qty,
                    (int) $item->item_qty,
                    'Supplier Invoice',
                    [
                        'invoice_number'      => $invoice->invoice_number,
                        'supplier_id'         => $invoice->supplier_id,
                        'supplier_invoice_id' => $invoice->id,
                    ]
                );
            }

            $invoice->update([
                'status'      => 'confirmed',
                'stock_added' => true,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Invoice confirmed and stock updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function showPaymentForm($id)
    {
        $invoice    = SupplierInvoice::with('supplier', 'currency')->findOrFail($id);
        $currencies = Currency::all();

        if (!$invoice->canPay()) {
            return redirect()->route('supplier-invoices.show', $id)
                ->with('error', 'Payment can only be recorded for confirmed invoices with outstanding balance.');
        }

        $nextPayId  = DB::table('supplier_invoice_payments')->max('id') + 1;
        $rcpNumber  = 'SPMT-' . str_pad($nextPayId, 5, '0', STR_PAD_LEFT);

        $paymentHistory = DB::table('supplier_invoice_payments')
            ->where('supplier_invoice_id', $id)
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('supplier_invoices.pay', compact('invoice', 'currencies', 'rcpNumber', 'paymentHistory'));
    }

    public function storePayment(Request $request, $id)
    {
        $invoice = SupplierInvoice::findOrFail($id);

        $request->validate([
            'amount'       => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
            'payment_date' => 'required|date',
            'payment_mode' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $amount = (float) $request->amount;

            DB::table('supplier_invoice_payments')->insert([
                'supplier_invoice_id' => $invoice->id,
                'payment_number'      => $request->payment_number ?? ('SPMT-' . time()),
                'payment_date'        => $request->payment_date,
                'amount'              => $amount,
                'payment_mode'        => $request->payment_mode,
                'reference'           => $request->reference,
                'remarks'             => $request->remarks,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $newPaid    = $invoice->paid_amount + $amount;
            $newBalance = max(0, $invoice->net_total - $newPaid);
            $status     = $newBalance <= 0 ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount'    => $newPaid,
                'balance_due'    => $newBalance,
                'payment_status' => $status,
            ]);

            DB::commit();

            $redirectUrl = $request->input('return_url');
            if (! $redirectUrl || ! str_starts_with($redirectUrl, url('/'))) {
                $redirectUrl = route('supplier-invoices.show', $invoice->id);
            }

            return response()->json([
                'success'  => true,
                'message'  => 'Payment recorded successfully.',
                'redirect' => $redirectUrl,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function printInvoice($id)
    {
        $invoice = SupplierInvoice::with(['items.product.uom', 'items.product.packSize', 'supplier', 'currency'])->findOrFail($id);
        return view('supplier_invoices.print', compact('invoice'));
    }

    public function destroy($id)
    {
        $this->checkStaffPermission('supplier-invoices', 'delete');
        $invoice = SupplierInvoice::findOrFail($id);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be deleted.');
        }

        $invoice->delete();
        return redirect()->route('supplier-invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}
