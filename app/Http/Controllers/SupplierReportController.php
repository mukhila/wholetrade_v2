<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierReportController extends Controller
{
    /**
     * Total Outstanding Report
     * Shows each supplier: total invoices, total amount, total paid, total balance
     */
    public function outstanding()
    {
        $data = $this->outstandingQuery()->get();

        return view('supplier_reports.outstanding', compact('data'));
    }

    public function exportOutstanding()
    {
        $data     = $this->outstandingQuery()->get();
        $filename = 'supplier_outstanding_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'No.',
                'Supplier Name',
                'Total Invoices',
                'Total Amount',
                'Total Paid',
                'Outstanding Balance',
            ]);

            foreach ($data as $i => $row) {
                fputcsv($handle, [
                    $i + 1,
                    $row->supplier_name,
                    $row->total_invoices,
                    number_format($row->total_amount, 2, '.', ''),
                    number_format($row->total_paid, 2, '.', ''),
                    number_format($row->total_balance, 2, '.', ''),
                ]);
            }

            // Grand total row
            if ($data->count() > 0) {
                fputcsv($handle, [
                    '',
                    'Grand Total',
                    $data->sum('total_invoices'),
                    number_format($data->sum('total_amount'), 2, '.', ''),
                    number_format($data->sum('total_paid'), 2, '.', ''),
                    number_format($data->sum('total_balance'), 2, '.', ''),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function outstandingQuery()
    {
        return DB::table('supplier_purchases as sp')
            ->join('associates as a', 'sp.supplier_id', '=', 'a.id')
            ->whereNull('sp.deleted_at')
            ->whereIn('sp.payment_status', ['unpaid', 'partial'])
            ->select(
                'a.id as supplier_id',
                'a.name as supplier_name',
                DB::raw('COUNT(sp.id) as total_invoices'),
                DB::raw('SUM(sp.purchase_amount) as total_amount'),
                DB::raw('SUM(sp.paid_amount) as total_paid'),
                DB::raw('SUM(sp.balance) as total_balance')
            )
            ->groupBy('a.id', 'a.name')
            ->orderBy('a.name');
    }

    /**
     * Individual Supplier Ledger
     * Shows all invoices for a supplier with invoice amount, paid, balance
     */
    public function ledger(Request $request)
    {
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();
        $supplier  = null;
        $ledger    = collect();
        $totals    = null;

        if ($request->filled('supplier_id')) {
            $supplier = Associate::find($request->supplier_id);
            $ledger   = $this->ledgerQuery($request->supplier_id, $request->status)->get();
            $totals   = [
                'purchase_amount' => $ledger->sum('purchase_amount'),
                'paid_amount'     => $ledger->sum('paid_amount'),
                'balance'         => $ledger->sum('balance'),
            ];
        }

        return view('supplier_reports.ledger', compact('suppliers', 'supplier', 'ledger', 'totals'));
    }

    public function exportLedger(Request $request)
    {
        $request->validate(['supplier_id' => 'required|exists:associates,id']);

        $supplier = Associate::findOrFail($request->supplier_id);
        $ledger   = $this->ledgerQuery($request->supplier_id, $request->status)->get();

        $statusLabel = $request->filled('status') ? '_' . $request->status : '';
        $filename    = 'ledger_' . str_replace(' ', '_', strtolower($supplier->name))
                       . $statusLabel . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($supplier, $ledger) {
            $handle = fopen('php://output', 'w');

            // Supplier info header rows
            fputcsv($handle, ['Supplier', $supplier->name]);
            if ($supplier->phone) {
                fputcsv($handle, ['Phone', $supplier->phone]);
            }
            if ($supplier->contact_person) {
                fputcsv($handle, ['Contact Person', $supplier->contact_person]);
            }
            fputcsv($handle, ['Exported', now()->format('d M Y, h:i A')]);
            fputcsv($handle, []);

            // Column headers
            fputcsv($handle, [
                'No.',
                'Invoice Number',
                'Purchase Date',
                'Invoice Amount',
                'Paid Amount',
                'Balance',
                'Status',
            ]);

            foreach ($ledger as $i => $row) {
                fputcsv($handle, [
                    $i + 1,
                    $row->invoice_number,
                    $row->purchase_date->format('Y-m-d'),
                    number_format($row->purchase_amount, 2, '.', ''),
                    number_format($row->paid_amount, 2, '.', ''),
                    number_format($row->balance, 2, '.', ''),
                    ucfirst($row->payment_status),
                ]);
            }

            // Totals row
            if ($ledger->count() > 0) {
                fputcsv($handle, []);
                fputcsv($handle, [
                    '',
                    'Total',
                    '',
                    number_format($ledger->sum('purchase_amount'), 2, '.', ''),
                    number_format($ledger->sum('paid_amount'), 2, '.', ''),
                    number_format($ledger->sum('balance'), 2, '.', ''),
                    '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function ledgerQuery(int $supplierId, ?string $status)
    {
        return SupplierPurchase::where('supplier_id', $supplierId)
            ->when($status, fn($q) => $q->where('payment_status', $status))
            ->orderBy('purchase_date');
    }

    /**
     * GIRO Payment Report
     * Shows upcoming (unpaid) GIRO payments sorted by giro_date
     */
    public function giro(Request $request)
    {
        $payments  = $this->giroQuery($request)->get();
        $suppliers = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();

        return view('supplier_reports.giro', compact('payments', 'suppliers'));
    }

    public function exportGiro(Request $request)
    {
        $payments  = $this->giroQuery($request)->get();
        $filename  = 'giro_payments_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
        ];

        $callback = function () use ($payments) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'No.',
                'Supplier Name',
                'Payment Ref',
                'Amount',
                'GIRO Date',
                'Invoices',
                'Status',
            ]);

            foreach ($payments as $i => $p) {
                $invoices = $p->items->map(fn($item) => $item->purchase->invoice_number ?? '?')->implode(', ');

                fputcsv($handle, [
                    $i + 1,
                    $p->supplier->name ?? '',
                    $p->reference_number ?? '',
                    number_format($p->total_amount, 2, '.', ''),
                    $p->giro_date ? $p->giro_date->format('Y-m-d') : '',
                    $invoices,
                    $p->giro_is_paid ? 'Paid' : 'Pending',
                ]);
            }

            if ($payments->count() > 0) {
                fputcsv($handle, []);
                fputcsv($handle, [
                    '', 'Total', '',
                    number_format($payments->sum('total_amount'), 2, '.', ''),
                    '', '', '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function giroQuery(Request $request)
    {
        $query = SupplierPurchasePayment::with('supplier', 'items.purchase')
            ->where('payment_mode', 'GIRO');

        if (!$request->boolean('show_paid')) {
            $query->where('giro_is_paid', false);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        return $query->orderBy('giro_date');
    }
}
