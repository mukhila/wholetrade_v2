<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Quotation;
use App\Models\Product;
use App\Models\SalesInvoice; // BUG-010 FIX: removed legacy Invoice import, using SalesInvoice throughout
use App\Models\SalesReceipt;
use App\Models\SupplierInvoice;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Redirect Super Admin to Super Admin Dashboard
        if ($user->isSuperAdmin()) {
            return redirect()->route('super_admin.dashboard');
        }

        $isSales = $user->hasRole('salesperson');
        $isStore = $user->hasRole('store_person');
        $isAdmin = $user->hasRole('admin');

        $data = [
            'isSales' => $isSales,
            'isStore' => $isStore,
            'isAdmin' => $isAdmin,
        ];

        // Salesperson Data
        if ($isSales || $isAdmin) {
            $data['pendingQuotes'] = Quotation::where('salesperson_id', $user->id)
                ->where('status', 'PENDING_ADMIN_APPROVAL')->count();

            $data['approvedQuotes'] = Quotation::where('salesperson_id', $user->id)
                ->whereIn('status', ['APPROVED_BY_ADMIN', 'CONVERTED_TO_INVOICE'])->count();

            $data['recentQuotes'] = Quotation::with('customer:id,name')
                ->where('salesperson_id', $user->id)
                ->latest()
                ->take(5)
                ->get();
        }

        // Storeperson Data (or Admin)
        if ($isStore || $isAdmin) {
            // BUG-011 FIX: filter for products actually below their minimum stock threshold.
            // Total stock in pieces = (boxes × pack_size) + loose pcs.
            $data['lowStockProducts'] = Product::where('status', true)
                ->where('min_stock_qty', '>', 0)
                ->whereRaw('(stock_qty_box * COALESCE(pack_size_qty, 1) + stock_qty_pcs) < min_stock_qty')
                ->take(5)
                ->get();

            $data['totalProducts'] = Product::count();
        }

        // Admin Specific Data
        if ($isAdmin) {
            $data['totalUsers']     = \App\Models\User::count();
            $data['totalCustomers'] = \App\Models\Associate::where('type', 'Customer')->count();

            // BUG-010 FIX: use SalesInvoice (current module) instead of legacy Invoice model.
            $data['recentInvoices'] = SalesInvoice::with('customer', 'salesperson')->latest()->take(5)->get();
            $data['totalRevenue']   = SalesInvoice::whereIn('status', ['confirmed', 'posted'])
                ->where('payment_status', 'paid')
                ->sum('net_total');

            // ── KPI Summary Cards (current month) ────────────────────────────
            $now        = Carbon::now();
            $monthStart = $now->copy()->startOfMonth();
            $monthEnd   = $now->copy()->endOfMonth();

            $data['monthlySales']    = (float) SalesInvoice::whereBetween('invoice_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled')
                ->sum('net_total');

            $data['monthlyReceipts'] = (float) SalesReceipt::whereBetween('receipt_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $data['monthlyExpenses'] = (float) SupplierInvoice::whereBetween('invoice_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled')
                ->sum('net_total');

            $data['monthlyPayments'] = (float) SupplierInvoice::whereBetween('invoice_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled')
                ->sum('paid_amount');

            // ── Chart Data — last 6 months ────────────────────────────────────
            $months = collect(range(5, 0))->map(fn($i) => $now->copy()->subMonths($i));

            $chartLabels      = [];
            $salesChart       = [];
            $collectionsChart = [];
            $expensesChart    = [];
            $paymentsChart    = [];

            foreach ($months as $m) {
                $start = $m->copy()->startOfMonth();
                $end   = $m->copy()->endOfMonth();

                $chartLabels[]      = $m->format('M Y');

                $salesChart[]       = (float) SalesInvoice::whereBetween('invoice_date', [$start, $end])
                    ->where('status', '!=', 'cancelled')->sum('net_total');

                $collectionsChart[] = (float) SalesReceipt::whereBetween('receipt_date', [$start, $end])
                    ->sum('amount');

                $expensesChart[]    = (float) SupplierInvoice::whereBetween('invoice_date', [$start, $end])
                    ->where('status', '!=', 'cancelled')->sum('net_total');

                $paymentsChart[]    = (float) SupplierInvoice::whereBetween('invoice_date', [$start, $end])
                    ->where('status', '!=', 'cancelled')->sum('paid_amount');
            }

            $data['chartLabels']      = $chartLabels;
            $data['salesChart']       = $salesChart;
            $data['collectionsChart'] = $collectionsChart;
            $data['expensesChart']    = $expensesChart;
            $data['paymentsChart']    = $paymentsChart;
        }

        return view('dashboard', $data);
    }
}
