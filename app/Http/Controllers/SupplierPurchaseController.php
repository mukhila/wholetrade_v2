<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\SupplierPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierPurchase::with('supplier')->latest();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        $purchases  = $query->paginate(20)->withQueryString();
        $suppliers  = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();

        return view('supplier_purchases.index', compact('purchases', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();
        return view('supplier_purchases.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('supplier-purchases', 'create');
        $request->validate([
            'supplier_id'    => 'required|exists:associates,id',
            'purchase_date'  => 'required|date',
            'invoice_number' => [
                'required',
                Rule::unique('supplier_purchases', 'invoice_number')
                    ->where('supplier_id', $request->supplier_id)
                    ->whereNull('deleted_at'),
            ],
            'purchase_amount'=> 'required|numeric|min:0.01',
            'gst_rate'       => 'nullable|numeric|min:0|max:100',
        ]);

        $gstRate   = (float) ($request->gst_rate ?? 0);
        $gstAmount = round($request->purchase_amount * $gstRate / (100 + $gstRate), 2);

        SupplierPurchase::create([
            'supplier_id'    => $request->supplier_id,
            'purchase_date'  => $request->purchase_date,
            'invoice_number' => strtoupper(trim($request->invoice_number)),
            'purchase_amount'=> $request->purchase_amount,
            'gst_rate'       => $gstRate,
            'gst_amount'     => $gstAmount,
            'paid_amount'    => 0,
            'balance'        => $request->purchase_amount,
            'payment_status' => 'unpaid',
            'remarks'        => $request->remarks,
        ]);

        return redirect()->route('supplier-purchases.index')
            ->with('success', 'Purchase added successfully.');
    }

    public function edit($id)
    {
        $purchase  = SupplierPurchase::findOrFail($id);
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();
        return view('supplier_purchases.edit', compact('purchase', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        $this->checkStaffPermission('supplier-purchases', 'edit');
        $purchase = SupplierPurchase::findOrFail($id);

        $request->validate([
            'supplier_id'    => 'required|exists:associates,id',
            'purchase_date'  => 'required|date',
            'invoice_number' => [
                'required',
                Rule::unique('supplier_purchases', 'invoice_number')
                    ->ignore($id)
                    ->where('supplier_id', $request->supplier_id)
                    ->whereNull('deleted_at'),
            ],
            'purchase_amount'=> 'required|numeric|min:' . $purchase->paid_amount,
            'gst_rate'       => 'nullable|numeric|min:0|max:100',
        ]);

        $gstRate    = (float) ($request->gst_rate ?? 0);
        $gstAmount  = round($request->purchase_amount * $gstRate / (100 + $gstRate), 2);
        $newBalance = $request->purchase_amount - $purchase->paid_amount;
        $status = $purchase->paid_amount <= 0 ? 'unpaid'
            : ($newBalance <= 0 ? 'paid' : 'partial');

        $purchase->update([
            'supplier_id'    => $request->supplier_id,
            'purchase_date'  => $request->purchase_date,
            'invoice_number' => strtoupper(trim($request->invoice_number)),
            'purchase_amount'=> $request->purchase_amount,
            'gst_rate'       => $gstRate,
            'gst_amount'     => $gstAmount,
            'balance'        => $newBalance,
            'payment_status' => $status,
            'remarks'        => $request->remarks,
        ]);

        return redirect()->route('supplier-purchases.index')
            ->with('success', 'Purchase updated successfully.');
    }

    public function destroy($id)
    {
        $this->checkStaffPermission('supplier-purchases', 'delete');
        $purchase = SupplierPurchase::findOrFail($id);

        if ($purchase->paid_amount > 0) {
            return back()->with('error', 'Cannot delete a purchase that has payments recorded.');
        }

        $purchase->delete();
        return back()->with('success', 'Purchase deleted.');
    }

    // ── Export ──────────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $query = SupplierPurchase::with('supplier')->latest();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        $purchases = $query->get();

        $filename = 'supplier_purchases_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($purchases) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Supplier Name',
                'Invoice Number',
                'Purchase Date',
                'Purchase Amount',
                'GST Rate (%)',
                'GST Amount',
                'Paid Amount',
                'Balance',
                'Status',
                'Remarks',
            ]);

            foreach ($purchases as $p) {
                fputcsv($handle, [
                    $p->supplier->name ?? '',
                    $p->invoice_number,
                    $p->purchase_date->format('Y-m-d'),
                    $p->purchase_amount,
                    $p->gst_rate,
                    $p->gst_amount,
                    $p->paid_amount,
                    $p->balance,
                    $p->payment_status,
                    $p->remarks ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Sample CSV template ─────────────────────────────────────────────────

    public function sampleCsv()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="supplier_purchases_sample.csv"',
            'Pragma'              => 'no-cache',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Supplier Name',
                'Invoice Number',
                'Purchase Date',
                'Purchase Amount',
                'GST Rate (%)',
                'Remarks',
            ]);

            fputcsv($handle, ['ABC Supplier Pte Ltd', 'INV-2024-001', '2024-01-15', '1000.00', '9', 'Sample remark']);
            fputcsv($handle, ['XYZ Trading Co', 'INV-2024-002', '2024-01-20', '2500.50', '0', '']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Import form ─────────────────────────────────────────────────────────

    public function importForm()
    {
        return view('supplier_purchases.import');
    }

    // ── Import process ──────────────────────────────────────────────────────

    public function import(Request $request)
    {
        $this->checkStaffPermission('supplier-purchases', 'create');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Strip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header  = fgetcsv($handle);
        $header  = array_map(fn($h) => strtolower(trim($h)), $header);

        $expected = ['supplier name', 'invoice number', 'purchase date', 'purchase amount', 'gst rate (%)', 'remarks'];
        $missing  = array_diff($expected, $header);

        if (!empty($missing)) {
            fclose($handle);
            return back()->with('error', 'Invalid CSV format. Missing columns: ' . implode(', ', $missing));
        }

        // Pre-load suppliers for fast lookup (case-insensitive)
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->get()
            ->keyBy(fn($s) => strtolower(trim($s->name)));

        $imported = 0;
        $skipped  = [];
        $row      = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (count($data) < 4 || array_filter($data) === []) {
                continue;
            }

            $mapped = array_combine($header, array_pad($data, count($header), ''));

            $supplierName = trim($mapped['supplier name'] ?? '');
            $invoiceNo    = strtoupper(trim($mapped['invoice number'] ?? ''));
            $dateStr      = trim($mapped['purchase date'] ?? '');
            $amount       = trim($mapped['purchase amount'] ?? '');
            $gstRate      = (float) ($mapped['gst rate (%)'] ?? 0);
            $remarks      = trim($mapped['remarks'] ?? '');

            // Validate supplier
            $supplier = $suppliers->get(strtolower($supplierName));
            if (!$supplier) {
                $skipped[] = "Row {$row}: Supplier \"{$supplierName}\" not found.";
                continue;
            }

            // Validate date
            $date = null;
            foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $fmt) {
                $d = \DateTime::createFromFormat($fmt, $dateStr);
                if ($d && $d->format($fmt) === $dateStr) {
                    $date = $d->format('Y-m-d');
                    break;
                }
            }
            if (!$date) {
                $skipped[] = "Row {$row}: Invalid date \"{$dateStr}\".";
                continue;
            }

            // Validate amount
            if (!is_numeric($amount) || (float) $amount <= 0) {
                $skipped[] = "Row {$row}: Invalid purchase amount \"{$amount}\".";
                continue;
            }

            // Validate invoice number
            if ($invoiceNo === '') {
                $skipped[] = "Row {$row}: Invoice number is empty.";
                continue;
            }

            // Check duplicate (same supplier + invoice, excluding soft-deleted)
            $exists = SupplierPurchase::where('supplier_id', $supplier->id)
                ->where('invoice_number', $invoiceNo)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $skipped[] = "Row {$row}: Invoice \"{$invoiceNo}\" already exists for {$supplierName}.";
                continue;
            }

            $purchaseAmount = (float) $amount;
            $gstAmount      = $gstRate > 0 ? round($purchaseAmount * $gstRate / (100 + $gstRate), 2) : 0;

            SupplierPurchase::create([
                'supplier_id'    => $supplier->id,
                'purchase_date'  => $date,
                'invoice_number' => $invoiceNo,
                'purchase_amount'=> $purchaseAmount,
                'gst_rate'       => $gstRate,
                'gst_amount'     => $gstAmount,
                'paid_amount'    => 0,
                'balance'        => $purchaseAmount,
                'payment_status' => 'unpaid',
                'remarks'        => $remarks ?: null,
            ]);

            $imported++;
        }

        fclose($handle);

        $message = "{$imported} purchase(s) imported successfully.";
        if (!empty($skipped)) {
            return redirect()->route('supplier-purchases.import')
                ->with('import_success', $message)
                ->with('import_skipped', $skipped);
        }

        return redirect()->route('supplier-purchases.index')->with('success', $message);
    }
}
