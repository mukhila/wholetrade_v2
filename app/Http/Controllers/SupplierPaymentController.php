<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchasePayment;
use App\Models\SupplierPurchasePaymentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierPurchasePayment::with('supplier', 'items.purchase')->latest();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $payments  = $query->paginate(20)->withQueryString();
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();

        return view('supplier_payments.index', compact('payments', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();
        return view('supplier_payments.create', compact('suppliers'));
    }

    /**
     * AJAX: get due invoices for a supplier
     */
    public function getDueInvoices(Request $request)
    {
        $request->validate(['supplier_id' => 'required|exists:associates,id']);

        $invoices = SupplierPurchase::where('supplier_id', $request->supplier_id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('purchase_date')
            ->get(['id', 'invoice_number', 'purchase_date', 'purchase_amount', 'paid_amount', 'balance']);

        return response()->json($invoices);
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('supplier-payments', 'create');
        $request->validate([
            'supplier_id'   => 'required|exists:associates,id',
            'payment_date'  => 'required|date',
            'payment_mode'  => 'required|in:Cash,Online Transfer,GIRO',
            'invoices'      => 'required|array|min:1',
            'invoices.*.id' => 'required|exists:supplier_purchases,id',
            'invoices.*.amount' => 'required|numeric|min:0.01',
            'reference_number' => 'required_if:payment_mode,Online Transfer',
            'giro_date'        => 'required_if:payment_mode,GIRO|nullable|date',
            'reference_number' => 'required_if:payment_mode,GIRO',
        ]);

        // Validate amounts don't exceed balances
        foreach ($request->invoices as $item) {
            $purchase = SupplierPurchase::find($item['id']);
            if (!$purchase || $purchase->supplier_id != $request->supplier_id) {
                return back()->withErrors(['invoices' => 'Invalid invoice selected.']);
            }
            if ($item['amount'] > $purchase->balance) {
                return back()->withErrors(['invoices' => "Payment for {$purchase->invoice_number} exceeds balance."]);
            }
        }

        DB::beginTransaction();
        try {
            $totalAmount = collect($request->invoices)->sum('amount');

            // BUG-005 FIX: create first, then overwrite number from auto-increment ID
            $payment = SupplierPurchasePayment::create([
                'supplier_id'      => $request->supplier_id,
                'payment_number'   => 'TEMP-' . uniqid('', true),
                'payment_date'     => $request->payment_date,
                'total_amount'     => $totalAmount,
                'payment_mode'     => $request->payment_mode,
                'reference_number' => $request->reference_number,
                'giro_date'        => $request->giro_date,
                'giro_is_paid'     => false,
                'remarks'          => $request->remarks,
            ]);

            $paymentNumber = 'SPAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT);
            $payment->update(['payment_number' => $paymentNumber]);

            foreach ($request->invoices as $item) {
                SupplierPurchasePaymentItem::create([
                    'payment_id'  => $payment->id,
                    'purchase_id' => $item['id'],
                    'amount_paid' => $item['amount'],
                ]);

                // Update purchase paid/balance
                $purchase = SupplierPurchase::find($item['id']);
                $newPaid    = $purchase->paid_amount + $item['amount'];
                $newBalance = $purchase->purchase_amount - $newPaid;
                $status     = $newBalance <= 0 ? 'paid' : 'partial';

                $purchase->update([
                    'paid_amount'    => $newPaid,
                    'balance'        => max(0, $newBalance),
                    'payment_status' => $status,
                ]);
            }

            DB::commit();
            return redirect()->route('supplier-payments.index')
                ->with('success', "Payment {$paymentNumber} recorded successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save payment: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $payment = SupplierPurchasePayment::with('supplier', 'items.purchase')->findOrFail($id);
        return view('supplier_payments.show', compact('payment'));
    }

    /**
     * Mark GIRO payment as paid (manual update)
     */
    public function markGiroPaid(Request $request, $id)
    {
        $this->checkStaffPermission('supplier-payments', 'edit');
        $payment = SupplierPurchasePayment::where('payment_mode', 'GIRO')->findOrFail($id);
        $payment->update(['giro_is_paid' => true]);

        return back()->with('success', 'GIRO payment marked as paid.');
    }
}
