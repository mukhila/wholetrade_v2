<?php

namespace App\Http\Controllers;

use App\Models\SalesReceipt;
use App\Models\SalesInvoice;
use App\Models\Associate;
use App\Models\Currency;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Paymode;
use App\Models\InvoiceReference;
use App\Models\CompanySetting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReceiptController extends Controller
{
    public function index()
    {
        $receipts = SalesReceipt::with('customer')->latest()->paginate(10);
        return view('sales.receipts.index', compact('receipts'));
    }

    public function create()
    {
        $customers = Associate::where('type', 'Customer')->where('status', true)->get();
        $currencies = Currency::where('is_default', 1)->get();
        $banks = Bank::withoutGlobalScope('subscriber')->where('status', true)->get();
        $bankAccounts = BankAccount::withoutGlobalScope('subscriber')->with(['bank' => fn($q) => $q->withoutGlobalScope('subscriber')])->where('status', true)->get();
        $paymodes = Paymode::withoutGlobalScope('subscriber')->where('status', true)->get();

        $nextId = SalesReceipt::max('id') + 1;
        $rcpNumber = 'RCP-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('sales.receipts.create', compact(
            'customers', 'currencies', 'banks', 'bankAccounts', 'paymodes', 'rcpNumber'
        ));
    }
    
    // API to fetch unpaid invoices for a customer (from both old and new systems)
    public function getPendingInvoices($customerId)
    {
        $result = [];

        // 1. Get OLD invoices from invoice_references table (legacy data)
        $oldInvoices = InvoiceReference::where('customer_id', $customerId)
            ->where('balance', '>', 0)
            ->orderBy('date', 'asc') // Oldest first for FIFO
            ->get();

        foreach ($oldInvoices as $inv) {
            $result[] = [
                'id' => $inv->id,
                'source' => 'old', // Identifier for old system
                'invoice_number' => $inv->invoice_no,
                'invoice_date' => $inv->date ? $inv->date->format('Y-m-d') : null,
                'net_total' => (float) $inv->net_total,
                'paid_amount' => (float) $inv->paid,
                'balance_due' => (float) $inv->balance,
            ];
        }

        // 2. Get NEW invoices from sales_invoices table (current system)
        $newInvoices = SalesInvoice::where('customer_id', $customerId)
            ->whereIn('status', ['confirmed', 'posted'])
            ->where(function($q) {
                $q->where('payment_status', '!=', 'paid')
                  ->orWhereNull('payment_status');
            })
            ->where('balance_due', '>', 0)
            ->orderBy('invoice_date', 'asc') // Oldest first for FIFO
            ->get(['id', 'invoice_number', 'invoice_date', 'net_total', 'paid_amount', 'balance_due']);

        foreach ($newInvoices as $inv) {
            $result[] = [
                'id' => $inv->id,
                'source' => 'new', // Identifier for new system
                'invoice_number' => $inv->invoice_number,
                'invoice_date' => $inv->invoice_date ? $inv->invoice_date->format('Y-m-d') : null,
                'net_total' => (float) $inv->net_total,
                'paid_amount' => (float) $inv->paid_amount,
                'balance_due' => (float) $inv->balance_due,
            ];
        }

        // Sort combined result by date (oldest first)
        usort($result, function($a, $b) {
            return strtotime($a['invoice_date'] ?? '1900-01-01') - strtotime($b['invoice_date'] ?? '1900-01-01');
        });

        return response()->json($result);
    }

    // API to get bank accounts by bank
    public function getBankAccounts($bankId)
    {
        $accounts = BankAccount::withoutGlobalScope('subscriber')
            ->where('bank_id', $bankId)
            ->where('status', true)
            ->get(['id', 'account_name', 'account_number', 'account_type']);

        return response()->json($accounts);
    }

    // API to fetch customer's invoice reference history
    public function getCustomerCreditHistory($customerId)
    {
        // Get all invoice references for this customer
        $history = InvoiceReference::where('customer_id', $customerId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Get the latest record to show current credit/balance
        $latestRecord = $history->first();

        // Calculate this month's total paid
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $thisMonthPaid = SalesReceipt::where('customer_id', $customerId)
            ->whereBetween('receipt_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'posted')
            ->sum('amount');

        return response()->json([
            'success' => true,
            'history' => $history,
            'latest' => $latestRecord ? [
                'id' => $latestRecord->id,
                'invoice_no' => $latestRecord->invoice_no,
                'date' => $latestRecord->date?->format('Y-m-d'),
                'net_total' => $latestRecord->net_total,
                'paid' => $latestRecord->paid,
                'balance' => $latestRecord->balance,
                'status' => $latestRecord->status,
            ] : null,
            'this_month_paid' => $thisMonthPaid,
        ]);
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('sales-receipts', 'create');
        $rules = [
            'customer_id' => 'required|exists:associates,id',
            'receipt_number' => 'required|unique:sales_receipts,receipt_number',
            'customer_paid_amount' => 'required|numeric|min:0.01',
            'allocations' => 'array', // Must be an array
        ];

        // Conditional validation for cheque
        if ($request->payment_mode === 'Cheque') {
            $rules['cheque_no'] = 'required|string';
            $rules['cheque_date'] = 'required|date';
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $customerPaid = floatval($request->customer_paid_amount);
            $writeOff = floatval($request->write_off_amount ?? 0);
            $receiptDate = $request->receipt_date ?? now();

            // 1. Create Receipt Header
            $receipt = SalesReceipt::create([
                'receipt_number' => $request->receipt_number,
                'receipt_date' => $receiptDate,
                'customer_id' => $request->customer_id,
                'currency_id' => $request->currency_id,
                'bank_id' => $request->bank_id,
                'bank_account_id' => $request->bank_account_id,
                'paymode_id' => $request->paymode_id,
                'amount' => $customerPaid,
                'unused_amount' => $customerPaid, // Start with full amount unused
                'write_off_amount' => $writeOff,
                'excess_amount' => 0,
                'payment_mode' => $request->payment_mode,
                'pay_to' => $request->pay_to,
                'reference' => $request->reference,
                'cheque_date' => $request->cheque_date,
                'cheque_no' => $request->cheque_no,
                'remarks' => $request->remarks,
                'status' => 'posted',
            ]);

            // 2. Allocate to Invoices
            $totalAllocated = 0;
            
            if (!empty($request->allocations) && is_array($request->allocations)) {
                foreach ($request->allocations as $alloc) {
                    $invId = $alloc['invoice_id'] ?? null;
                    $source = $alloc['source'] ?? 'new'; 
                    $amountToPay = floatval($alloc['amount'] ?? 0);

                    if (!$invId || $amountToPay <= 0) continue;

                    if ($source === 'old') {
                        // Legacy Invoice Handling
                        $invoiceRef = InvoiceReference::where('id', $invId)->lockForUpdate()->first();
                        if ($invoiceRef) {
                            $payable = min($amountToPay, $invoiceRef->balance);
                            if ($payable > 0) {
                                $invoiceRef->paid += $payable;
                                $invoiceRef->balance -= $payable;
                                
                                if ($invoiceRef->balance <= 0.01) {
                                    $invoiceRef->balance = 0;
                                    $invoiceRef->status = 'CLOSED';
                                } else {
                                    $invoiceRef->status = 'PARTIAL';
                                }
                                
                                $invoiceRef->save();
                                $totalAllocated += $payable;
                            }
                        }
                    } else {
                        // Current System Invoice Handling
                        $invoice = SalesInvoice::where('id', $invId)->lockForUpdate()->first();
                        if ($invoice) {
                            // Ensure we don't overpay beyond balance due
                            $currentBalance = $invoice->balance_due;
                            $payable = min($amountToPay, $currentBalance);

                            if ($payable > 0) {
                                // 1. Create Pivot Record
                                $receipt->invoices()->attach($invoice->id, [
                                    'allocated_amount' => $payable,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);

                                // 2. Update Invoice Stats
                                $invoice->paid_amount += $payable;
                                $invoice->balance_due -= $payable;

                                // 3. Update Status
                                if ($invoice->balance_due <= 0.01) {
                                    $invoice->balance_due = 0;
                                    $invoice->payment_status = 'paid';
                                } else {
                                    $invoice->payment_status = 'partial';
                                }
                                $invoice->save();

                                // 4. Audit Log
                                AuditLog::log(
                                    AuditLog::ACTION_PAYMENT,
                                    AuditLog::MODULE_SALES_INVOICE,
                                    $invoice,
                                    [
                                        'reference_code' => $invoice->invoice_number,
                                        'description' => "Payment of {$payable} received via Receipt {$receipt->receipt_number}",
                                        'metadata' => [
                                            'receipt_id' => $receipt->id,
                                            'receipt_number' => $receipt->receipt_number,
                                            'paid_amount' => $payable,
                                            'remaining_balance' => $invoice->balance_due,
                                        ],
                                    ]
                                );

                                $totalAllocated += $payable;
                            }
                        }
                    }
                }
            }

            // 3. Update Receipt Balances
            // unused_amount = (Paid - Allocated)
            // excess_amount = same as unused if it stays in account
            $remaining = max(0, $customerPaid - $totalAllocated);
            
            $receipt->unused_amount = $remaining;
            $receipt->excess_amount = $remaining;
            $receipt->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Receipt created and allocated successfully!',
                'receipt_id' => $receipt->id,
                'redirect' => route('sales-receipts.show', $receipt->id) // Redirect to show page instead of index
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Receipt Creation Error: '.$e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified receipt.
     */
    public function show($id)
    {
        $receipt = SalesReceipt::with(['customer', 'currency', 'invoices'])
            ->findOrFail($id);

        // Get default currency if receipt doesn't have one
        $defaultCurrency = Currency::where('is_default', 1)->first();
        $companySettings = CompanySetting::first();

        // Fetch all invoices for this customer (Old and New)
        $customerId = $receipt->customer_id;
        
        // Old Invoices
        $oldInvoices = InvoiceReference::where('customer_id', $customerId)
            ->orderBy('date', 'desc')
            ->get();
            
        // New Invoices
        $newInvoices = SalesInvoice::where('customer_id', $customerId)
            ->orderBy('invoice_date', 'desc')
            ->get();

        return view('sales.receipts.show', compact('receipt', 'companySettings', 'defaultCurrency', 'oldInvoices', 'newInvoices'));
    }

    /**
     * Print receipt in bill format.
     */
    public function print($id)
    {
        $receipt = SalesReceipt::with(['customer', 'currency', 'invoices'])
            ->findOrFail($id);

        // Get default currency if receipt doesn't have one
        $defaultCurrency = Currency::where('is_default', 1)->first();
        $companySettings = CompanySetting::first();

        return view('sales.receipts.print', compact('receipt', 'companySettings', 'defaultCurrency'));
    }
    /**
     * Print selected invoices (Statement/Summary).
     */
    public function printSelected(Request $request)
    {
        $selected = json_decode($request->invoices, true);
        if (!$selected || !is_array($selected)) {
            abort(400, 'No invoices selected');
        }

        $invoices = collect();
        foreach ($selected as $item) {
            if ($item['type'] === 'new') {
                $inv = SalesInvoice::find($item['id']);
                if ($inv) {
                    $invoices->push([
                        'invoice_no' => $inv->invoice_number,
                        'date' => $inv->invoice_date,
                        'net_total' => $inv->net_total,
                        'paid_amount' => $inv->paid_amount,
                        'balance' => $inv->balance_due,
                        'type' => 'new'
                    ]);
                }
            } else {
                $inv = InvoiceReference::find($item['id']);
                if ($inv) {
                    $invoices->push([
                        'invoice_no' => $inv->invoice_no,
                        'date' => $inv->date,
                        'net_total' => $inv->net_total,
                        'paid_amount' => $inv->paid,
                        'balance' => $inv->balance,
                        'type' => 'old'
                    ]);
                }
            }
        }

        $customer = Associate::find($request->customer_id);
        $companySettings = CompanySetting::first();
        $defaultCurrency = Currency::where('is_default', 1)->first();

        return view('sales.receipts.print-selected', compact('invoices', 'customer', 'companySettings', 'defaultCurrency'));
    }
}
