<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class GstReportController extends Controller
{
    public function index(Request $request)
    {
        $suppliers      = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();
        $companySetting = CompanySetting::first();

        $years = DB::table('supplier_purchases')
            ->whereNull('deleted_at')
            ->selectRaw('YEAR(purchase_date) as yr')
            ->distinct()
            ->orderByDesc('yr')
            ->pluck('yr');

        $records      = $this->buildDetailQuery($request)->get();
        $monthSummary = $this->buildMonthQuery($request)->get();

        $totals = [
            'purchase_amount' => $records->sum('purchase_amount'),
            'gst_amount'      => $records->sum('gst_amount'),
            'net_amount'      => $records->sum(fn($r) => $r->purchase_amount - $r->gst_amount),
            'count'           => $records->count(),
        ];

        return view('supplier_reports.gst_report', compact(
            'suppliers', 'years', 'records', 'monthSummary', 'totals', 'companySetting'
        ));
    }

    public function print(Request $request)
    {
        $companySetting = CompanySetting::first();
        $suppliers      = Associate::where('type', 'Supplier')->where('status', true)->orderBy('name')->get();

        $records      = $this->buildDetailQuery($request)->get();
        $monthSummary = $this->buildMonthQuery($request)->get();

        $totals = [
            'purchase_amount' => $records->sum('purchase_amount'),
            'gst_amount'      => $records->sum('gst_amount'),
            'net_amount'      => $records->sum(fn($r) => $r->purchase_amount - $r->gst_amount),
            'count'           => $records->count(),
        ];

        $filterLabel = $this->buildFilterLabel($request, $suppliers);

        return view('supplier_reports.gst_print', compact(
            'companySetting', 'records', 'monthSummary', 'totals', 'filterLabel'
        ));
    }

    public function exportCsv(Request $request)
    {
        $records  = $this->buildDetailQuery($request)->get();
        $filename = 'gst_report_detailed_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($records) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['No.', 'Invoice No.', 'Date', 'Supplier Name', 'Invoice Amount', 'GST Rate (%)', 'GST Amount', 'Net Amount (excl. GST)', 'Payment Status']);
            foreach ($records as $i => $r) {
                fputcsv($f, [
                    $i + 1,
                    $r->invoice_number,
                    \Carbon\Carbon::parse($r->purchase_date)->format('d M Y'),
                    $r->supplier_name,
                    number_format($r->purchase_amount, 2, '.', ''),
                    number_format($r->gst_rate, 2, '.', ''),
                    number_format($r->gst_amount, 2, '.', ''),
                    number_format($r->purchase_amount - $r->gst_amount, 2, '.', ''),
                    ucfirst($r->payment_status),
                ]);
            }
            // Totals row
            fputcsv($f, [
                '', 'TOTAL', '', '',
                number_format($records->sum('purchase_amount'), 2, '.', ''),
                '',
                number_format($records->sum('gst_amount'), 2, '.', ''),
                number_format($records->sum(fn($r) => $r->purchase_amount - $r->gst_amount), 2, '.', ''),
                '',
            ]);
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportMonthlyCsv(Request $request)
    {
        $rows     = $this->buildMonthQuery($request)->get();
        $filename = 'gst_report_monthly_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['No.', 'Month', 'Total Invoices', 'Total Purchase Amount', 'Total GST Amount', 'Net Amount (excl. GST)']);
            foreach ($rows as $i => $r) {
                $month = \DateTime::createFromFormat('!m', $r->mo)->format('F') . ' ' . $r->yr;
                fputcsv($f, [
                    $i + 1,
                    $month,
                    $r->total_invoices,
                    number_format($r->total_amount, 2, '.', ''),
                    number_format($r->total_gst, 2, '.', ''),
                    number_format($r->total_amount - $r->total_gst, 2, '.', ''),
                ]);
            }
            fputcsv($f, [
                '', 'TOTAL',
                $rows->sum('total_invoices'),
                number_format($rows->sum('total_amount'), 2, '.', ''),
                number_format($rows->sum('total_gst'), 2, '.', ''),
                number_format($rows->sum(fn($r) => $r->total_amount - $r->total_gst), 2, '.', ''),
            ]);
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    // ── Shared query builders ─────────────────────────────────────────────────

    private function buildDetailQuery(Request $request)
    {
        return $this->baseQuery($request)->select(
            'sp.id',
            'sp.invoice_number',
            'sp.purchase_date',
            'sp.purchase_amount',
            'sp.gst_rate',
            'sp.gst_amount',
            'sp.payment_status',
            'a.name as supplier_name'
        )->orderBy('sp.purchase_date');
    }

    private function buildMonthQuery(Request $request)
    {
        return $this->baseQuery($request)
            ->selectRaw('YEAR(sp.purchase_date) as yr, MONTH(sp.purchase_date) as mo,
                         COUNT(sp.id) as total_invoices,
                         SUM(sp.purchase_amount) as total_amount,
                         SUM(sp.gst_amount) as total_gst')
            ->groupByRaw('YEAR(sp.purchase_date), MONTH(sp.purchase_date)')
            ->orderByRaw('YEAR(sp.purchase_date), MONTH(sp.purchase_date)');
    }

    private function baseQuery(Request $request)
    {
        $q = DB::table('supplier_purchases as sp')
            ->join('associates as a', 'sp.supplier_id', '=', 'a.id')
            ->whereNull('sp.deleted_at');

        if ($request->filled('supplier_id')) {
            $q->where('sp.supplier_id', $request->supplier_id);
        }
        if ($request->filled('year')) {
            $q->whereYear('sp.purchase_date', $request->year);
        }
        if ($request->filled('month')) {
            $q->whereMonth('sp.purchase_date', $request->month);
        }

        return $q;
    }

    private function buildFilterLabel(Request $request, $suppliers): string
    {
        $parts = [];
        if ($request->filled('supplier_id')) {
            $name = $suppliers->find($request->supplier_id)?->name ?? 'Selected Supplier';
            $parts[] = "Supplier: $name";
        }
        if ($request->filled('month')) {
            $parts[] = 'Month: ' . \DateTime::createFromFormat('!m', $request->month)->format('F');
        }
        if ($request->filled('year')) {
            $parts[] = 'Year: ' . $request->year;
        }
        return $parts ? implode('  |  ', $parts) : 'All Records';
    }
}
