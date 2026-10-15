<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class CustomerReportController extends Controller
{
    public function index(Request $request)
    {
        $customers = Associate::where('type', 'Customer')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Summary always loads — shows all customers by default, filtered when dates/status applied
        $summary = $this->getSummaryData($request);

        return view('reports.customer', compact('customers', 'summary'));
    }

    public function customerDetail(Request $request)
    {
        $detailed = $this->getDetailedData($request);
        $customer = Associate::find($request->customer_id);

        return response()->json([
            'customer_name' => $customer?->name ?? 'Unknown',
            'invoices'      => $detailed->values(),
            'totals'        => [
                'net_total'   => $detailed->sum('net_total'),
                'paid_amount' => $detailed->sum('paid_amount'),
                'balance_due' => $detailed->sum('balance_due'),
            ],
        ]);
    }

    public function printDetailed(Request $request)
    {
        $customers = Associate::where('type', 'Customer')->orderBy('name')->get(['id', 'name']);
        $detailed  = $this->getDetailedData($request);
        $summary   = $this->getSummaryData($request);

        return view('reports.print.customer-detailed', compact('detailed', 'summary'));
    }

    public function printDetailedThermal(Request $request)
    {
        $detailed        = $this->getDetailedData($request);
        $summary         = $this->getSummaryData($request);
        $companySettings = \App\Models\CompanySetting::first();
        $filters         = $request->only(['date_from', 'date_to', 'customer_id', 'payment_status']);

        $rawbtPayload80 = base64_encode(
            \App\Http\Controllers\PrintBridgeController::buildReportPayload($detailed, $summary, $companySettings, $filters, 42)
        );
        $rawbtPayload58 = base64_encode(
            \App\Http\Controllers\PrintBridgeController::buildReportPayload($detailed, $summary, $companySettings, $filters, 32)
        );

        return view('reports.print.thermal-customer-detailed',
            compact('detailed', 'summary', 'companySettings', 'filters', 'rawbtPayload80', 'rawbtPayload58'));
    }

    public function printSummary(Request $request)
    {
        $summary = $this->getSummaryData($request);
        return view('reports.print.customer-summary', compact('summary'));
    }

    public function export(Request $request)
    {
        $this->checkStaffPermission('reports', 'export');
        $type = $request->get('type', 'detailed');

        if ($type === 'summary') {
            return $this->exportSummary($request);
        }

        return $this->exportDetailed($request);
    }

    // ── Data builders ─────────────────────────────────────────────────────────

    public function getDetailedData(Request $request): \Illuminate\Support\Collection
    {
        $query = DB::table('sales_invoices as si')
            ->join('associates as a', 'si.customer_id', '=', 'a.id')
            ->whereIn('si.status', ['confirmed', 'posted'])
            ->whereNull('si.deleted_at')
            ->select(
                'a.id as customer_id',
                'a.name as customer_name',
                'si.invoice_number',
                'si.invoice_date',
                'si.net_total',
                'si.paid_amount',
                'si.balance_due',
                'si.payment_status',
                'si.status'
            )
            ->orderBy('a.name')
            ->orderBy('si.invoice_date', 'desc');

        $this->applyFilters($query, $request);

        return $query->get()->map(fn($r) => (array) $r);
    }

    public function getSummaryData(Request $request): \Illuminate\Support\Collection
    {
        $query = DB::table('sales_invoices as si')
            ->join('associates as a', 'si.customer_id', '=', 'a.id')
            ->whereIn('si.status', ['confirmed', 'posted'])
            ->whereNull('si.deleted_at')
            ->select(
                'a.id as customer_id',
                'a.name as customer_name',
                DB::raw('COUNT(si.id) as invoice_count'),
                DB::raw('SUM(si.net_total) as total_sales'),
                DB::raw('SUM(si.paid_amount) as total_paid'),
                DB::raw('SUM(si.balance_due) as total_balance')
            )
            ->groupBy('a.id', 'a.name')
            ->orderByDesc('total_sales');

        $this->applyFilters($query, $request);

        return $query->get()->map(fn($r) => (array) $r);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('si.invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('si.invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $query->where('si.customer_id', $request->customer_id);
        }
        if ($request->filled('payment_status')) {
            $query->where('si.payment_status', $request->payment_status);
        }
    }

    // ── CSV exports ───────────────────────────────────────────────────────────

    private function exportDetailed(Request $request)
    {
        $rows = $this->getDetailedData($request);
        $filename = 'customer_detailed_report_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Customer', 'Invoice No', 'Date', 'Total', 'Paid', 'Balance', 'Payment Status', 'Status']);
            foreach ($rows as $r) {
                fputcsv($f, [
                    $r['customer_name'],
                    $r['invoice_number'],
                    $r['invoice_date'],
                    number_format($r['net_total'], 2, '.', ''),
                    number_format($r['paid_amount'], 2, '.', ''),
                    number_format($r['balance_due'], 2, '.', ''),
                    ucfirst($r['payment_status']),
                    ucfirst($r['status']),
                ]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function exportSummary(Request $request)
    {
        $rows = $this->getSummaryData($request);
        $filename = 'customer_summary_report_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Customer', 'Invoices', 'Total Sales', 'Total Paid', 'Balance Due']);
            foreach ($rows as $r) {
                fputcsv($f, [
                    $r['customer_name'],
                    $r['invoice_count'],
                    number_format($r['total_sales'], 2, '.', ''),
                    number_format($r['total_paid'], 2, '.', ''),
                    number_format($r['total_balance'], 2, '.', ''),
                ]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}
