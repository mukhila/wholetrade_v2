<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\InvoiceReference;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class SalesReportController extends Controller
{
    /**
     * BUG-006 FIX: replaced full in-memory load + PHP sort + slice with a SQL UNION ALL
     * that paginates at the database layer. This prevents memory exhaustion on large datasets.
     */
    public function getSalesReport(Request $request)
    {
        $perPage = max(1, (int) $request->get('per_page', 15));
        $page    = max(1, (int) $request->get('page', 1));
        $source  = $request->get('source', 'all');
        $offset  = ($page - 1) * $perPage;

        [$currentSql, $currentBindings] = $this->buildCurrentSubQuery($request, $source);
        [$legacySql,  $legacyBindings]  = $this->buildLegacySubQuery($request, $source);

        $unionSql      = "({$currentSql}) UNION ALL ({$legacySql})";
        $unionBindings = array_merge($currentBindings, $legacyBindings);

        // Count at DB level — no full table load required
        $total = (int) DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM ({$unionSql}) AS combined",
            $unionBindings
        )->cnt;

        if ($request->boolean('print')) {
            // For print: load all rows (no LIMIT) — acceptable since print is intentional
            $rows = DB::select("{$unionSql} ORDER BY invoice_date DESC", $unionBindings);
        } else {
            $rows = DB::select(
                "{$unionSql} ORDER BY invoice_date DESC LIMIT {$perPage} OFFSET {$offset}",
                $unionBindings
            );
        }

        $results  = collect($rows)->map(fn($r) => (array) $r);
        $enriched = $this->enrichInvoiceResults($results);
        $kpis     = $this->calculateSalesKPIs($request, $source);

        if ($request->boolean('print')) {
            return view('reports.print.sales', [
                'invoices' => $enriched,
                'filters'  => $request->all(),
                'kpis'     => $kpis,
            ]);
        }

        return response()->json([
            'data'       => $enriched,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
            'kpis' => $kpis,
        ]);
    }

    private function buildCurrentSubQuery(Request $request, string $source): array
    {
        if ($source === 'legacy') {
            return [
                "SELECT NULL AS id, NULL AS invoice_number, NULL AS invoice_date, NULL AS customer_id,
                        NULL AS salesperson_id, NULL AS total, NULL AS tax_amount, NULL AS paid_amount, NULL AS balance,
                        NULL AS payment_status, NULL AS status, NULL AS created_at, NULL AS source,
                        NULL AS total_qty
                 WHERE 1=0",
                [],
            ];
        }

        $sql = "SELECT id, invoice_number, invoice_date, customer_id, salesperson_id,
                       net_total AS total, tax_amount, paid_amount, balance_due AS balance,
                       payment_status, status, created_at, 'current' AS source,
                       (SELECT COALESCE(SUM(COALESCE(box_qty,0) + COALESCE(item_qty,0)), 0)
                        FROM sales_invoice_items WHERE sales_invoice_id = sales_invoices.id) AS total_qty
                FROM sales_invoices
                WHERE status IN ('confirmed','posted') AND deleted_at IS NULL";

        $bindings = [];

        if ($request->filled('date_from')) {
            $sql .= ' AND DATE(invoice_date) >= ?';
            $bindings[] = $request->date_from;
        }
        if ($request->filled('date_to')) {
            $sql .= ' AND DATE(invoice_date) <= ?';
            $bindings[] = $request->date_to;
        }
        if ($request->filled('customer_id')) {
            $sql .= ' AND customer_id = ?';
            $bindings[] = $request->customer_id;
        }
        if ($request->filled('salesperson_id')) {
            $sql .= ' AND salesperson_id = ?';
            $bindings[] = $request->salesperson_id;
        }
        if ($request->filled('payment_status')) {
            $sql .= ' AND payment_status = ?';
            $bindings[] = $request->payment_status;
        }
        if ($request->filled('search')) {
            $sql .= ' AND invoice_number LIKE ?';
            $bindings[] = '%' . $request->search . '%';
        }

        return [$sql, $bindings];
    }

    private function buildLegacySubQuery(Request $request, string $source): array
    {
        // Legacy table has no salesperson concept; exclude it when filtering by salesperson
        if ($source === 'current' || $request->filled('salesperson_id')) {
            return [
                "SELECT NULL AS id, NULL AS invoice_number, NULL AS invoice_date, NULL AS customer_id,
                        NULL AS salesperson_id, NULL AS total, NULL AS tax_amount, NULL AS paid_amount, NULL AS balance,
                        NULL AS payment_status, NULL AS status, NULL AS created_at, NULL AS source,
                        NULL AS total_qty
                 WHERE 1=0",
                [],
            ];
        }

        $sql = "SELECT id, invoice_no AS invoice_number, date AS invoice_date, customer_id,
                       NULL AS salesperson_id, net_total AS total, NULL AS tax_amount, paid AS paid_amount,
                       balance, status AS payment_status, 'OPEN' AS status,
                       created_at, 'legacy' AS source, NULL AS total_qty
                FROM invoice_references
                WHERE 1=1";

        $bindings = [];

        if ($request->filled('date_from')) {
            $sql .= ' AND DATE(date) >= ?';
            $bindings[] = $request->date_from;
        }
        if ($request->filled('date_to')) {
            $sql .= ' AND DATE(date) <= ?';
            $bindings[] = $request->date_to;
        }
        if ($request->filled('customer_id')) {
            $sql .= ' AND customer_id = ?';
            $bindings[] = $request->customer_id;
        }
        if ($request->filled('payment_status')) {
            $sql .= ' AND status = ?';
            $bindings[] = $this->mapPaymentStatusToLegacy($request->payment_status);
        }
        if ($request->filled('search')) {
            $sql .= ' AND invoice_no LIKE ?';
            $bindings[] = '%' . $request->search . '%';
        }

        return [$sql, $bindings];
    }

    public function printSalesReport(Request $request)
    {
        $request->merge(['print' => true]);
        return $this->getSalesReport($request);
    }

    public function printSalesReportByCustomer(Request $request)
    {
        $source = $request->get('source', 'current');

        [$currentSql, $currentBindings] = $this->buildCurrentSubQuery($request, $source);
        [$legacySql,  $legacyBindings]  = $this->buildLegacySubQuery($request,  $source);

        $unionSql      = "({$currentSql}) UNION ALL ({$legacySql})";
        $unionBindings = array_merge($currentBindings, $legacyBindings);

        $rows     = DB::select("{$unionSql} ORDER BY invoice_date DESC", $unionBindings);
        $enriched = $this->enrichInvoiceResults(collect($rows)->map(fn($r) => (array) $r));
        $kpis     = $this->calculateSalesKPIs($request, $source);

        $grouped = collect($enriched)
            ->groupBy(fn($inv) => $inv['customer_name'] ?? 'Unknown')
            ->sortKeys();

        return view('reports.print.sales-customer-wise', [
            'grouped' => $grouped,
            'filters' => $request->all(),
            'kpis'    => $kpis,
        ]);
    }

    // ── Summary Report ────────────────────────────────────────────────────────

    public function getSalesSummary(Request $request)
    {
        $groupBy = $request->get('group_by', 'customer');

        $query = DB::table('sales_invoices as si')
            ->whereIn('si.status', ['confirmed', 'posted'])
            ->whereNull('si.deleted_at');

        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->subscriber_id) {
            $subId = auth()->user()->subscriber_id;
            $query->where(fn($q) => $q->where('si.subscriber_id', $subId)->orWhereNull('si.subscriber_id'));
        }

        if ($request->filled('date_from'))      $query->whereDate('si.invoice_date', '>=', $request->date_from);
        if ($request->filled('date_to'))        $query->whereDate('si.invoice_date', '<=', $request->date_to);
        if ($request->filled('customer_id'))    $query->where('si.customer_id', $request->customer_id);
        if ($request->filled('salesperson_id')) $query->where('si.salesperson_id', $request->salesperson_id);
        if ($request->filled('payment_status')) $query->where('si.payment_status', $request->payment_status);

        match ($groupBy) {
            'salesperson' => $query
                ->leftJoin('users as u', 'si.salesperson_id', '=', 'u.id')
                ->select(
                    'u.id as group_id',
                    DB::raw("COALESCE(u.name, 'Unassigned') as group_name"),
                    DB::raw('COUNT(si.id) as invoice_count'),
                    DB::raw('SUM(si.net_total) as total_sales'),
                    DB::raw('SUM(si.paid_amount) as total_paid'),
                    DB::raw('SUM(si.balance_due) as total_balance')
                )
                ->groupBy('u.id', 'u.name')
                ->orderByDesc('total_sales'),

            'month' => $query
                ->select(
                    DB::raw("DATE_FORMAT(si.invoice_date, '%Y-%m') as group_id"),
                    DB::raw("DATE_FORMAT(si.invoice_date, '%b %Y') as group_name"),
                    DB::raw('COUNT(si.id) as invoice_count'),
                    DB::raw('SUM(si.net_total) as total_sales'),
                    DB::raw('SUM(si.paid_amount) as total_paid'),
                    DB::raw('SUM(si.balance_due) as total_balance')
                )
                ->groupBy(DB::raw("DATE_FORMAT(si.invoice_date, '%Y-%m')"), DB::raw("DATE_FORMAT(si.invoice_date, '%b %Y')"))
                ->orderBy(DB::raw("DATE_FORMAT(si.invoice_date, '%Y-%m')"), 'desc'),

            default => $query
                ->join('associates as a', 'si.customer_id', '=', 'a.id')
                ->select(
                    'a.id as group_id',
                    'a.name as group_name',
                    DB::raw('COUNT(si.id) as invoice_count'),
                    DB::raw('SUM(si.net_total) as total_sales'),
                    DB::raw('SUM(si.paid_amount) as total_paid'),
                    DB::raw('SUM(si.balance_due) as total_balance')
                )
                ->groupBy('a.id', 'a.name')
                ->orderByDesc('total_sales'),
        };

        $rows = $query->get()->map(fn($r) => (array) $r);

        $totals = [
            'invoice_count' => $rows->sum('invoice_count'),
            'total_sales'   => round($rows->sum('total_sales'), 2),
            'total_paid'    => round($rows->sum('total_paid'), 2),
            'total_balance' => round($rows->sum('total_balance'), 2),
        ];

        if ($request->boolean('print')) {
            return view('reports.print.sales-summary', [
                'rows'    => $rows,
                'totals'  => $totals,
                'groupBy' => $groupBy,
                'filters' => $request->all(),
            ]);
        }

        return response()->json(['data' => $rows, 'totals' => $totals]);
    }

    public function printSalesSummary(Request $request)
    {
        $request->merge(['print' => true]);
        return $this->getSalesSummary($request);
    }

    public function getSalesSummaryDateWise(Request $request)
    {
        $cogsSql = 'COALESCE((
            SELECT SUM(
                COALESCE(pp.cost_price_box,  0) * sii.box_qty  +
                COALESCE(pp.cost_price_item, 0) * sii.item_qty
            )
            FROM sales_invoice_items sii
            LEFT JOIN product_prices pp ON pp.product_id = sii.product_id
            WHERE sii.sales_invoice_id = si.id
        ), 0)';

        $query = DB::table('sales_invoices as si')
            ->whereIn('si.status', ['confirmed', 'posted'])
            ->whereNull('si.deleted_at')
            ->select(
                DB::raw('DATE(si.invoice_date) as date'),
                DB::raw('COUNT(si.id) as invoice_count'),
                DB::raw('SUM(si.net_total) as total_sales'),
                DB::raw('SUM(si.paid_amount) as total_paid'),
                DB::raw('SUM(si.balance_due) as total_balance'),
                DB::raw("SUM(si.net_total - COALESCE(si.tax_amount, 0)) as total_revenue"),
                DB::raw("SUM({$cogsSql}) as total_cost")
            )
            ->groupBy(DB::raw('DATE(si.invoice_date)'))
            ->orderBy(DB::raw('DATE(si.invoice_date)'), 'desc');

        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->subscriber_id) {
            $subId = auth()->user()->subscriber_id;
            $query->where(fn($q) => $q->where('si.subscriber_id', $subId)->orWhereNull('si.subscriber_id'));
        }
        if ($request->filled('date_from'))      $query->whereDate('si.invoice_date', '>=', $request->date_from);
        if ($request->filled('date_to'))        $query->whereDate('si.invoice_date', '<=', $request->date_to);
        if ($request->filled('customer_id'))    $query->where('si.customer_id', $request->customer_id);
        if ($request->filled('salesperson_id')) $query->where('si.salesperson_id', $request->salesperson_id);
        if ($request->filled('payment_status')) $query->where('si.payment_status', $request->payment_status);

        $rows = $query->get()->map(function ($r) {
            $revenue = (float) $r->total_revenue;
            $cost    = (float) $r->total_cost;
            $profit  = $revenue - $cost;
            $absPct  = $revenue > 0 ? round(abs($profit) / $revenue * 100, 1) : 0;
            return [
                'date'          => $r->date,
                'invoice_count' => (int) $r->invoice_count,
                'total_sales'   => round((float) $r->total_sales,   2),
                'total_paid'    => round((float) $r->total_paid,    2),
                'total_balance' => round((float) $r->total_balance, 2),
                'total_revenue' => round($revenue, 2),
                'total_cost'    => round($cost,    2),
                'gross_profit'  => round($profit,  2),
                'profit_pct'    => $profit >= 0 ? $absPct : 0,
                'loss_pct'      => $profit <  0 ? $absPct : 0,
            ];
        });

        $totalRevenue = $rows->sum('total_revenue');
        $totalCost    = $rows->sum('total_cost');
        $totalProfit  = $totalRevenue - $totalCost;
        $totalAbsPct  = $totalRevenue > 0 ? round(abs($totalProfit) / $totalRevenue * 100, 1) : 0;

        return response()->json([
            'data'   => $rows,
            'totals' => [
                'invoice_count' => $rows->sum('invoice_count'),
                'total_sales'   => round($rows->sum('total_sales'),   2),
                'total_paid'    => round($rows->sum('total_paid'),    2),
                'total_balance' => round($rows->sum('total_balance'), 2),
                'total_revenue' => round($totalRevenue, 2),
                'total_cost'    => round($totalCost,    2),
                'gross_profit'  => round($totalProfit,  2),
                'profit_pct'    => $totalProfit >= 0 ? $totalAbsPct : 0,
                'loss_pct'      => $totalProfit <  0 ? $totalAbsPct : 0,
            ],
        ]);
    }

    public function printSalesSummaryDateWise(Request $request)
    {
        // Reuse the existing date-wise query, but render as a view
        $response = $this->getSalesSummaryDateWise($request);
        $payload  = $response->getData(true);

        return view('reports.print.sales-summary-date-wise', [
            'rows'    => $payload['data'],
            'totals'  => $payload['totals'],
            'filters' => $request->all(),
        ]);
    }

    public function exportSalesSummaryDateWise(Request $request)
    {
        $this->checkStaffPermission('reports', 'export');
        $response = $this->getSalesSummaryDateWise($request);
        $payload  = $response->getData(true);
        $rows     = $payload['data'];
        $filename = 'sales_summary_date_wise_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Date', 'Invoices', 'Total Sales', 'Total Paid', 'Outstanding', 'Revenue (ex-Tax)', 'COGS', 'Gross Profit/Loss', 'Profit %', 'Loss %']);
            foreach ($rows as $r) {
                fputcsv($f, [
                    $r['date'],
                    $r['invoice_count'],
                    number_format($r['total_sales'],   2, '.', ''),
                    number_format($r['total_paid'],    2, '.', ''),
                    number_format($r['total_balance'], 2, '.', ''),
                    number_format($r['total_revenue'], 2, '.', ''),
                    number_format($r['total_cost'],    2, '.', ''),
                    number_format($r['gross_profit'],  2, '.', ''),
                    $r['profit_pct'] . '%',
                    $r['loss_pct']   . '%',
                ]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportSalesSummary(Request $request)
    {
        $this->checkStaffPermission('reports', 'export');
        $request->merge(['print' => false]);
        $result  = $this->getSalesSummary($request);
        $payload = $result->getData(true);
        $rows    = $payload['data'];
        $groupBy = $request->get('group_by', 'customer');

        $groupLabel = match ($groupBy) {
            'salesperson' => 'Salesperson',
            'month'       => 'Month',
            default       => 'Customer',
        };

        $filename = 'sales_summary_' . $groupBy . '_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($rows, $groupLabel) {
            $f = fopen('php://output', 'w');
            fputcsv($f, [$groupLabel, 'Invoices', 'Total Sales', 'Total Paid', 'Outstanding Balance']);
            foreach ($rows as $r) {
                fputcsv($f, [
                    $r['group_name'],
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

    // ── Profit & Loss Report ──────────────────────────────────────────────────

    public function getProfitLossReport(Request $request)
    {
        $perPage = max(1, (int) $request->get('per_page', 15));
        $page    = max(1, (int) $request->get('page', 1));
        $offset  = ($page - 1) * $perPage;

        $applyFilters = function ($q) use ($request) {
            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->subscriber_id) {
                $subId = auth()->user()->subscriber_id;
                $q->where(fn($sq) => $sq->where('si.subscriber_id', $subId)->orWhereNull('si.subscriber_id'));
            }
            if ($request->filled('date_from'))      $q->whereDate('si.invoice_date', '>=', $request->date_from);
            if ($request->filled('date_to'))        $q->whereDate('si.invoice_date', '<=', $request->date_to);
            if ($request->filled('customer_id'))    $q->where('si.customer_id', $request->customer_id);
            if ($request->filled('salesperson_id')) $q->where('si.salesperson_id', $request->salesperson_id);
            if ($request->filled('search'))         $q->where('si.invoice_number', 'like', "%{$request->search}%");
        };

        // Correlated subquery: COGS per invoice (box cost + item cost)
        $cogsSql = 'COALESCE((
            SELECT SUM(
                COALESCE(pp.cost_price_box,  0) * sii.box_qty  +
                COALESCE(pp.cost_price_item, 0) * sii.item_qty
            )
            FROM sales_invoice_items sii
            LEFT JOIN product_prices pp ON pp.product_id = sii.product_id
            WHERE sii.sales_invoice_id = si.id
        ), 0)';

        // ── Grand totals (all matching rows) ─────────────────────────────────
        $totalsQuery = DB::table('sales_invoices as si')
            ->whereIn('si.status', ['confirmed', 'posted'])
            ->whereNull('si.deleted_at')
            ->select(
                DB::raw('COUNT(si.id) as invoice_count'),
                DB::raw("SUM(si.net_total - COALESCE(si.tax_amount, 0)) as total_revenue"),
                DB::raw("SUM({$cogsSql}) as total_cost")
            );
        $applyFilters($totalsQuery);
        $totalsRow    = $totalsQuery->first();
        $totalRev     = (float) ($totalsRow->total_revenue ?? 0);
        $totalCost    = (float) ($totalsRow->total_cost    ?? 0);
        $totalProfit  = $totalRev - $totalCost;
        $invoiceCount = (int)   ($totalsRow->invoice_count ?? 0);

        // ── Paginated rows ───────────────────────────────────────────────────
        $rowsQuery = DB::table('sales_invoices as si')
            ->leftJoin('associates as a', 'a.id', '=', 'si.customer_id')
            ->leftJoin('users as u',      'u.id', '=', 'si.salesperson_id')
            ->whereIn('si.status', ['confirmed', 'posted'])
            ->whereNull('si.deleted_at')
            ->select(
                'si.invoice_number',
                'si.invoice_date',
                DB::raw('a.name as customer_name'),
                DB::raw('u.name as salesperson_name'),
                'si.net_total',
                DB::raw("(si.net_total - COALESCE(si.tax_amount, 0)) as revenue"),
                DB::raw("{$cogsSql} as total_cost")
            )
            ->orderByDesc('si.invoice_date')
            ->skip($offset)
            ->take($perPage);
        $applyFilters($rowsQuery);

        $rows = $rowsQuery->get()->map(function ($r) {
            $revenue = (float) $r->revenue;
            $cost    = (float) $r->total_cost;
            $profit  = $revenue - $cost;
            return [
                'invoice_number'   => $r->invoice_number,
                'invoice_date'     => $r->invoice_date,
                'customer_name'    => $r->customer_name    ?? 'N/A',
                'salesperson_name' => $r->salesperson_name ?? '-',
                'net_total'        => round((float) $r->net_total, 2),
                'revenue'          => round($revenue, 2),
                'total_cost'       => round($cost,    2),
                'gross_profit'     => round($profit,  2),
                'margin_pct'       => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0,
            ];
        });

        if ($request->boolean('print')) {
            return view('reports.print.profit-loss', [
                'rows'    => $rows,
                'totals'  => compact('totalRev', 'totalCost', 'totalProfit', 'invoiceCount') + [
                    'margin_pct' => $totalRev > 0 ? round($totalProfit / $totalRev * 100, 1) : 0,
                ],
                'filters' => $request->all(),
            ]);
        }

        return response()->json([
            'data'   => $rows,
            'totals' => [
                'invoice_count' => $invoiceCount,
                'total_revenue' => round($totalRev,    2),
                'total_cost'    => round($totalCost,   2),
                'gross_profit'  => round($totalProfit, 2),
                'margin_pct'    => $totalRev > 0 ? round($totalProfit / $totalRev * 100, 1) : 0,
            ],
            'pagination' => [
                'total'        => $invoiceCount,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => $invoiceCount > 0 ? (int) ceil($invoiceCount / $perPage) : 1,
                'from'         => $invoiceCount > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $invoiceCount),
            ],
        ]);
    }

    public function printProfitLossReport(Request $request)
    {
        $request->merge(['print' => true, 'per_page' => 9999]);
        return $this->getProfitLossReport($request);
    }

    public function getProfitLossDaySummary(Request $request)
    {
        $cogsSql = 'COALESCE((
            SELECT SUM(
                COALESCE(pp.cost_price_box,  0) * sii.box_qty  +
                COALESCE(pp.cost_price_item, 0) * sii.item_qty
            )
            FROM sales_invoice_items sii
            LEFT JOIN product_prices pp ON pp.product_id = sii.product_id
            WHERE sii.sales_invoice_id = si.id
        ), 0)';

        $query = DB::table('sales_invoices as si')
            ->whereIn('si.status', ['confirmed', 'posted'])
            ->whereNull('si.deleted_at')
            ->select(
                DB::raw('DATE(si.invoice_date) as date'),
                DB::raw('COUNT(si.id) as invoice_count'),
                DB::raw("SUM(si.net_total - COALESCE(si.tax_amount, 0)) as total_revenue"),
                DB::raw("SUM({$cogsSql}) as total_cost")
            )
            ->groupBy(DB::raw('DATE(si.invoice_date)'))
            ->orderBy(DB::raw('DATE(si.invoice_date)'), 'desc');

        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->subscriber_id) {
            $subId = auth()->user()->subscriber_id;
            $query->where(fn($q) => $q->where('si.subscriber_id', $subId)->orWhereNull('si.subscriber_id'));
        }
        if ($request->filled('date_from'))      $query->whereDate('si.invoice_date', '>=', $request->date_from);
        if ($request->filled('date_to'))        $query->whereDate('si.invoice_date', '<=', $request->date_to);
        if ($request->filled('customer_id'))    $query->where('si.customer_id', $request->customer_id);
        if ($request->filled('salesperson_id')) $query->where('si.salesperson_id', $request->salesperson_id);

        $rows = $query->get()->map(function ($r) {
            $revenue   = (float) $r->total_revenue;
            $cost      = (float) $r->total_cost;
            $profit    = $revenue - $cost;
            $absPct    = $revenue > 0 ? round(abs($profit) / $revenue * 100, 1) : 0;
            return [
                'date'          => $r->date,
                'invoice_count' => (int) $r->invoice_count,
                'total_revenue' => round($revenue, 2),
                'total_cost'    => round($cost,    2),
                'gross_profit'  => round($profit,  2),
                'profit_pct'    => $profit >= 0 ? $absPct : 0,
                'loss_pct'      => $profit <  0 ? $absPct : 0,
            ];
        });

        $totalRevenue = $rows->sum('total_revenue');
        $totalCost    = $rows->sum('total_cost');
        $totalProfit  = $totalRevenue - $totalCost;
        $totalAbsPct  = $totalRevenue > 0 ? round(abs($totalProfit) / $totalRevenue * 100, 1) : 0;

        return response()->json([
            'data'   => $rows,
            'totals' => [
                'invoice_count' => $rows->sum('invoice_count'),
                'total_revenue' => round($totalRevenue, 2),
                'total_cost'    => round($totalCost,    2),
                'gross_profit'  => round($totalProfit,  2),
                'profit_pct'    => $totalProfit >= 0 ? $totalAbsPct : 0,
                'loss_pct'      => $totalProfit <  0 ? $totalAbsPct : 0,
            ],
        ]);
    }

    public function exportProfitLossReport(Request $request)
    {
        $this->checkStaffPermission('reports', 'export');
        $request->merge(['per_page' => 9999, 'page' => 1]);
        $result  = $this->getProfitLossReport($request);
        $payload = $result->getData(true);
        $rows    = $payload['data'];
        $filename = 'profit_loss_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Invoice No', 'Date', 'Customer', 'Salesperson', 'Invoice Total', 'Revenue (ex-Tax)', 'COGS', 'Gross Profit', 'Margin %']);
            foreach ($rows as $r) {
                fputcsv($f, [
                    $r['invoice_number'],
                    $r['invoice_date'],
                    $r['customer_name'],
                    $r['salesperson_name'],
                    number_format($r['net_total'],    2, '.', ''),
                    number_format($r['revenue'],      2, '.', ''),
                    number_format($r['total_cost'],   2, '.', ''),
                    number_format($r['gross_profit'], 2, '.', ''),
                    $r['margin_pct'] . '%',
                ]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function exportSalesReport(Request $request)
    {
        $this->checkStaffPermission('reports', 'export');
        $source = $request->get('source', 'all');

        $salesInvoices = collect();
        if ($source !== 'legacy') {
            $query = SalesInvoice::with(['customer', 'salesperson'])
                ->whereIn('status', ['confirmed', 'posted']);

            if ($request->filled('date_from')) {
                $query->whereDate('invoice_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('invoice_date', '<=', $request->date_to);
            }
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->filled('salesperson_id')) {
                $query->where('salesperson_id', $request->salesperson_id);
            }
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }
            if ($request->filled('search')) {
                $query->where('invoice_number', 'like', "%{$request->search}%");
            }

            $salesInvoices = $query->orderBy('invoice_date', 'desc')->get();
        }

        $legacyInvoices = collect();
        if ($source !== 'current') {
            $query = InvoiceReference::with('customer');

            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->filled('payment_status')) {
                $legacyStatus = $this->mapPaymentStatusToLegacy($request->payment_status);
                $query->where('status', $legacyStatus);
            }
            if ($request->filled('search')) {
                $query->where('invoice_no', 'like', "%{$request->search}%");
            }

            if (!$request->filled('salesperson_id')) {
                $legacyInvoices = $query->orderBy('date', 'desc')->get();
            }
        }

        $filename = 'sales_report_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($salesInvoices, $legacyInvoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Source', 'Invoice No', 'Invoice Date', 'Customer',
                'Salesperson', 'Qty', 'Total Amount', 'Paid Amount', 'Balance', 'Payment Status',
            ]);

            foreach ($salesInvoices as $invoice) {
                $qty = $invoice->items()->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(box_qty,0) + COALESCE(item_qty,0)'));
                fputcsv($file, [
                    'Current',
                    $invoice->invoice_number,
                    $invoice->invoice_date instanceof \DateTime ? $invoice->invoice_date->format('Y-m-d') : $invoice->invoice_date,
                    $invoice->customer->name ?? 'N/A',
                    $invoice->salesperson->name ?? 'N/A',
                    (int) $qty,
                    number_format($invoice->net_total, 2, '.', ''),
                    number_format($invoice->paid_amount, 2, '.', ''),
                    number_format($invoice->balance_due, 2, '.', ''),
                    ucfirst($invoice->payment_status),
                ]);
            }

            foreach ($legacyInvoices as $invoice) {
                fputcsv($file, [
                    'Legacy',
                    $invoice->invoice_no,
                    $invoice->date instanceof \DateTime ? $invoice->date->format('Y-m-d') : $invoice->date,
                    $invoice->customer->name ?? $invoice->customer_name ?? 'N/A',
                    '-',
                    '-',
                    number_format($invoice->net_total, 2, '.', ''),
                    number_format($invoice->paid, 2, '.', ''),
                    number_format($invoice->balance, 2, '.', ''),
                    $invoice->status,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function mapPaymentStatusToLegacy($status)
    {
        $mapping = [
            'unpaid' => 'OPEN',
            'partial' => 'PARTIAL',
            'paid' => 'PAID',
        ];
        return $mapping[strtolower($status)] ?? $status;
    }

    private function enrichInvoiceResults($results)
    {
        $customerIds = $results->pluck('customer_id')->filter()->unique()->toArray();
        $salespersonIds = $results->pluck('salesperson_id')->filter()->unique()->toArray();

        $customers = Associate::whereIn('id', $customerIds)->pluck('name', 'id');
        $salespersons = User::whereIn('id', $salespersonIds)->pluck('name', 'id');

        return $results->map(function($invoice) use ($customers, $salespersons) {
            $invoice = (array) $invoice;

            $customerId = $invoice['customer_id'] ?? null;
            $invoice['customer_name'] = $customers[$customerId] ?? $invoice['customer_name'] ?? 'N/A';

            $salespersonId = $invoice['salesperson_id'] ?? null;
            $invoice['salesperson_name'] = $salespersonId ? ($salespersons[$salespersonId] ?? 'N/A') : '-';

            $source = $invoice['source'] ?? 'current';
            $paymentStatus = $invoice['payment_status'] ?? '';

            $invoice['payment_status_display'] = $source === 'legacy'
                ? ($paymentStatus ?: 'OPEN')
                : ucfirst($paymentStatus ?: 'unpaid');

            return $invoice;
        })->toArray();
    }

    private function calculateSalesKPIs(Request $request, $source = 'all')
    {
        // Current invoices — apply ALL active filters
        $currentQuery = SalesInvoice::whereIn('status', ['confirmed', 'posted']);
        if ($request->filled('date_from')) {
            $currentQuery->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $currentQuery->whereDate('invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $currentQuery->where('customer_id', $request->customer_id);
        }
        if ($request->filled('salesperson_id')) {
            $currentQuery->where('salesperson_id', $request->salesperson_id);
        }
        if ($request->filled('payment_status')) {
            $currentQuery->where('payment_status', $request->payment_status);
        }
        if ($request->filled('search')) {
            $currentQuery->where('invoice_number', 'like', "%{$request->search}%");
        }

        $currentTotal = $source !== 'legacy' ? $currentQuery->sum('net_total') : 0;
        $currentCount = $source !== 'legacy' ? $currentQuery->count() : 0;

        // Legacy invoices — apply ALL applicable filters
        $legacyQuery = InvoiceReference::query();
        if ($request->filled('date_from')) {
            $legacyQuery->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $legacyQuery->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $legacyQuery->where('customer_id', $request->customer_id);
        }
        if ($request->filled('payment_status')) {
            $legacyQuery->where('status', $this->mapPaymentStatusToLegacy($request->payment_status));
        }
        if ($request->filled('search')) {
            $legacyQuery->where('invoice_no', 'like', "%{$request->search}%");
        }
        // salesperson_id has no legacy equivalent — exclude legacy when filtering by salesperson
        if ($request->filled('salesperson_id')) {
            $legacyQuery->whereRaw('1 = 0');
        }

        $legacyTotal = $source !== 'current' ? $legacyQuery->sum('net_total') : 0;
        $legacyCount = $source !== 'current' ? $legacyQuery->count() : 0;

        $totalSalesValue = $currentTotal + $legacyTotal;
        $totalInvoices = $currentCount + $legacyCount;
        $avgOrderValue = $totalInvoices > 0 ? $totalSalesValue / $totalInvoices : 0;

        $currentOutstanding = $source !== 'legacy'
            ? SalesInvoice::whereIn('status', ['confirmed', 'posted'])
                ->where('balance_due', '>', 0)
                ->when($request->filled('date_from'),       fn($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
                ->when($request->filled('date_to'),         fn($q) => $q->whereDate('invoice_date', '<=', $request->date_to))
                ->when($request->filled('customer_id'),     fn($q) => $q->where('customer_id', $request->customer_id))
                ->when($request->filled('salesperson_id'),  fn($q) => $q->where('salesperson_id', $request->salesperson_id))
                ->when($request->filled('payment_status'),  fn($q) => $q->where('payment_status', $request->payment_status))
                ->sum('balance_due')
            : 0;

        $legacyOutstanding = $source !== 'current'
            ? InvoiceReference::where('balance', '>', 0)
                ->when($request->filled('date_from'),      fn($q) => $q->whereDate('date', '>=', $request->date_from))
                ->when($request->filled('date_to'),        fn($q) => $q->whereDate('date', '<=', $request->date_to))
                ->when($request->filled('customer_id'),    fn($q) => $q->where('customer_id', $request->customer_id))
                ->when($request->filled('payment_status'), fn($q) => $q->where('status', $this->mapPaymentStatusToLegacy($request->payment_status)))
                ->when($request->filled('salesperson_id'), fn($q) => $q->whereRaw('1 = 0'))
                ->sum('balance')
            : 0;

        return [
            'total_sales_value' => round($currentTotal + $legacyTotal, 2),
            'total_invoices' => $totalInvoices,
            'avg_order_value' => round($avgOrderValue, 2),
            'total_outstanding' => round($currentOutstanding + $legacyOutstanding, 2),
            'current_invoices_count' => $currentCount,
            'legacy_invoices_count' => $legacyCount,
            'top_customers' => $this->getTopCustomers($request, $source),
            'top_salespersons' => $this->getTopSalespersons($request, $source),
        ];
    }

    private function getTopCustomers(Request $request, $source = 'all')
    {
        $customerSales = [];

        if ($source !== 'legacy') {
            $query = DB::table('sales_invoices')
                ->join('associates', 'sales_invoices.customer_id', '=', 'associates.id')
                ->whereIn('sales_invoices.status', ['confirmed', 'posted'])
                ->whereNull('sales_invoices.deleted_at')
                ->selectRaw('associates.id, associates.name as customer_name, SUM(sales_invoices.net_total) as total_sales')
                ->groupBy('associates.id', 'associates.name');

            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->subscriber_id) {
                $query->where(function($q) {
                    $q->where('sales_invoices.subscriber_id', auth()->user()->subscriber_id)
                      ->orWhereNull('sales_invoices.subscriber_id');
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('sales_invoices.invoice_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('sales_invoices.invoice_date', '<=', $request->date_to);
            }
            if ($request->filled('customer_id')) {
                $query->where('sales_invoices.customer_id', $request->customer_id);
            }

            foreach ($query->get() as $customer) {
                $customerSales[$customer->id] = [
                    'customer_name' => $customer->customer_name,
                    'total_sales' => (float) $customer->total_sales,
                ];
            }
        }

        if ($source !== 'current') {
            $legacyCustomers = InvoiceReference::leftJoin('associates', 'invoice_references.customer_id', '=', 'associates.id')
                ->selectRaw('invoice_references.customer_id, COALESCE(associates.name, invoice_references.customer_name) as customer_name, SUM(invoice_references.net_total) as total_sales')
                ->when($request->filled('date_from'), fn($q) => $q->whereDate('invoice_references.date', '>=', $request->date_from))
                ->when($request->filled('date_to'), fn($q) => $q->whereDate('invoice_references.date', '<=', $request->date_to))
                ->when($request->filled('customer_id'), fn($q) => $q->where('invoice_references.customer_id', $request->customer_id))
                ->groupBy('invoice_references.customer_id', 'associates.name', 'invoice_references.customer_name')
                ->get();

            foreach ($legacyCustomers as $customer) {
                $id = $customer->customer_id ?? 'legacy_' . md5($customer->customer_name);
                if (isset($customerSales[$id])) {
                    $customerSales[$id]['total_sales'] += (float) $customer->total_sales;
                } else {
                    $customerSales[$id] = [
                        'customer_name' => $customer->customer_name,
                        'total_sales' => (float) $customer->total_sales,
                    ];
                }
            }
        }

        uasort($customerSales, fn($a, $b) => $b['total_sales'] <=> $a['total_sales']);
        return array_slice(array_values($customerSales), 0, 5);
    }

    private function getTopSalespersons(Request $request, $source = 'all')
    {
        if ($source === 'legacy') {
            return [];
        }

        $query = DB::table('sales_invoices')
            ->join('users', 'sales_invoices.salesperson_id', '=', 'users.id')
            ->whereIn('sales_invoices.status', ['confirmed', 'posted'])
            ->whereNull('sales_invoices.deleted_at')
            ->selectRaw('users.name as salesperson_name, SUM(sales_invoices.net_total) as total_sales')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->limit(5);

        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->subscriber_id) {
            $query->where(function($q) {
                $q->where('sales_invoices.subscriber_id', auth()->user()->subscriber_id)
                  ->orWhereNull('sales_invoices.subscriber_id');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sales_invoices.invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sales_invoices.invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $query->where('sales_invoices.customer_id', $request->customer_id);
        }
        if ($request->filled('salesperson_id')) {
            $query->where('sales_invoices.salesperson_id', $request->salesperson_id);
        }

        return $query->get()->map(fn($row) => (array) $row)->toArray();
    }
}
