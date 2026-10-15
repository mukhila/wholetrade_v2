<?php

namespace App\Http\Controllers;

use App\Models\InvoiceReference;
use App\Models\SalesReceipt;
use App\Models\Currency;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OldSalesInvoiceController extends Controller
{
    /**
     * Display listing of old sales invoices from invoice_references table.
     */
    public function index(Request $request)
    {
        $query = InvoiceReference::with('customer')->orderBy('date', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Balance filter
        if ($request->filled('balance_filter')) {
            if ($request->balance_filter === 'with_balance') {
                $query->where('balance', '>', 0);
            } elseif ($request->balance_filter === 'paid') {
                $query->where('balance', '<=', 0);
            }
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('old-sales-invoices.index', compact('invoices'));
    }

    /**
     * Show receipt form for a specific old invoice.
     */
    public function showReceiptForm($id)
    {
        $invoice = InvoiceReference::with('customer')->findOrFail($id);
        $currencies = Currency::where('is_default', 1)->get();

        // Generate receipt number
        $nextId = SalesReceipt::max('id') + 1;
        $rcpNumber = 'RCP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        $companySettings = CompanySetting::first();

        return view('old-sales-invoices.receipt', compact('invoice', 'currencies', 'rcpNumber', 'companySettings'));
    }

    /**
     * Store receipt for old invoice.
     */
    public function storeReceipt(Request $request, $id)
    {
        $request->validate([
            'receipt_number' => 'required|unique:sales_receipts,receipt_number',
            'amount' => 'required|numeric|min:0.01',
            'receipt_date' => 'required|date',
        ]);

        $invoice = InvoiceReference::findOrFail($id);

        DB::beginTransaction();
        try {
            // 1. Create Receipt
            $receipt = SalesReceipt::create([
                'receipt_number' => $request->receipt_number,
                'receipt_date' => $request->receipt_date,
                'customer_id' => $invoice->customer_id,
                'currency_id' => $request->currency_id,
                'amount' => $request->amount,
                'unused_amount' => 0, // Fully allocated to this old invoice
                'payment_mode' => $request->payment_mode,
                'reference' => $request->reference,
                'remarks' => $request->remarks ?? "Payment for old invoice: {$invoice->invoice_no}",
                'status' => 'posted',
            ]);

            // 2. Update Invoice Reference
            $invoice->paid += floatval($request->amount);
            $invoice->balance = $invoice->net_total - $invoice->paid;

            // Ensure balance doesn't go negative
            if ($invoice->balance < 0) {
                $invoice->balance = 0;
            }
            $invoice->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Receipt created successfully!',
                'receipt_id' => $receipt->id,
                'redirect' => route('old-sales-invoices.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Print invoice with outstanding balance.
     */
    public function print($id)
    {
        $invoice = InvoiceReference::with('customer')->findOrFail($id);
        $companySettings = CompanySetting::first();
        $defaultCurrency = Currency::where('is_default', 1)->first();

        // Get payment history for this invoice
        $paymentHistory = SalesReceipt::where('customer_id', $invoice->customer_id)
            ->where('remarks', 'like', "%{$invoice->invoice_no}%")
            ->orWhere(function($q) use ($invoice) {
                $q->where('customer_id', $invoice->customer_id)
                  ->whereDate('receipt_date', '>=', $invoice->date);
            })
            ->orderBy('receipt_date', 'asc')
            ->get();

        return view('old-sales-invoices.print', compact('invoice', 'companySettings', 'defaultCurrency', 'paymentHistory'));
    }
}
