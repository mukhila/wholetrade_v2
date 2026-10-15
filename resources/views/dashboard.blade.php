@extends('layouts.app')

@push('styles')
<style>
    .kpi-card { border-left: 4px solid; transition: transform .15s; }
    .kpi-card:hover { transform: translateY(-2px); }
    .kpi-card.sales    { border-color: #0d6efd; }
    .kpi-card.receipt  { border-color: #198754; }
    .kpi-card.expense  { border-color: #fd7e14; }
    .kpi-card.payment  { border-color: #6f42c1; }

    .kpi-icon { width: 44px; height: 44px; border-radius: 50%; display:flex; align-items:center; justify-content:center; }
    .kpi-icon.sales   { background: rgba(13,110,253,.12); color:#0d6efd; }
    .kpi-icon.receipt { background: rgba(25,135,84,.12);  color:#198754; }
    .kpi-icon.expense { background: rgba(253,126,20,.12); color:#fd7e14; }
    .kpi-icon.payment { background: rgba(111,66,193,.12); color:#6f42c1; }

    .kpi-label  { font-size: .72rem; text-transform: uppercase; letter-spacing: .6px; color: #6c757d; margin-bottom: 2px; }
    .kpi-value  { font-size: 1.45rem; font-weight: 700; line-height: 1.2; }
    .kpi-sub    { font-size: .75rem; color: #6c757d; margin-top: 4px; }

    @keyframes pulse {
        0%, 100% { opacity: 0.4; transform: scale(0.9); }
        50% { opacity: 1; transform: scale(1.2); }
    }
    .animate-pulse {
        animation: pulse 2s infinite ease-in-out;
    }
</style>
@endpush

@section('content')
<!-- Page Title -->
<div class="page-title-head d-flex align-items-center">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Dashboard</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </div>
</div>

<!-- Welcome Message -->
<div class="row">
    <div class="col-12">
        <div class="card bg-primary-subtle border-primary border-opacity-25 shadow-none text-primary">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-md-8">
                        <h4 class="m-0 fw-semibold">Welcome back, {{ Auth::user()->name }}!</h4>
                        <p class="mb-0 mt-1 opacity-75">Here is an overview of your activity.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-75 backdrop-blur-sm border border-primary border-opacity-10 rounded-3 px-3 py-2 shadow-sm text-start">
                            <div class="kpi-icon sales p-2 rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="ti ti-calendar-stats fs-18"></i>
                            </div>
                            <div>
                                <div id="dashboard-date" class="fw-semibold text-dark fs-13" style="line-height: 1.2;"></div>
                                <div id="dashboard-time" class="text-muted fs-12 d-flex align-items-center gap-1" style="line-height: 1.2;">
                                    <span class="clock-dot animate-pulse bg-success" style="display: inline-block; width: 6px; height: 6px; border-radius: 50%;"></span>
                                    <span id="clock-text" class="font-monospace"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        function update() {
            const now = new Date();
            const dateEl = document.getElementById('dashboard-date');
            const timeEl = document.getElementById('clock-text');
            if (dateEl) dateEl.textContent = now.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            if (timeEl) timeEl.textContent = now.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }
        update();
        setInterval(update, 1000);
    })();
</script>

{{-- ══════════════════════════════════════════════════════════════
     ADMIN SECTION
══════════════════════════════════════════════════════════════ --}}
@if($isAdmin)

{{-- ── KPI Cards ─────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    {{-- Sales --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card sales mb-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon sales flex-shrink-0">
                    <i data-lucide="trending-up" style="width:22px;height:22px;"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="kpi-label">Sales</div>
                    <div class="kpi-value text-primary">{{ number_format($monthlySales ?? 0, 2) }}</div>
                    <div class="kpi-sub">This month · Invoiced</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Receipt / Collection --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card receipt mb-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon receipt flex-shrink-0">
                    <i data-lucide="wallet" style="width:22px;height:22px;"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="kpi-label">Receipt</div>
                    <div class="kpi-value text-success">{{ number_format($monthlyReceipts ?? 0, 2) }}</div>
                    <div class="kpi-sub">This month · Collected</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Expenses --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card expense mb-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon expense flex-shrink-0">
                    <i data-lucide="receipt" style="width:22px;height:22px;"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="kpi-label">Expenses</div>
                    <div class="kpi-value" style="color:#fd7e14;">{{ number_format($monthlyExpenses ?? 0, 2) }}</div>
                    <div class="kpi-sub">This month · Supplier invoices</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card payment mb-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon payment flex-shrink-0">
                    <i data-lucide="credit-card" style="width:22px;height:22px;"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="kpi-label">Payment</div>
                    <div class="kpi-value" style="color:#6f42c1;">{{ number_format($monthlyPayments ?? 0, 2) }}</div>
                    <div class="kpi-sub">This month · Paid to suppliers</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Charts ────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    {{-- Sales vs Collection --}}
    <div class="col-xl-6">
        <div class="card h-100 mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="header-title mb-0">Sales vs Collection</h5>
                    <small class="text-muted">Last 6 months comparison</small>
                </div>
                <div class="d-flex gap-3 align-items-center" style="font-size:.78rem;">
                    <span><span class="badge" style="background:#0d6efd;">&nbsp;</span> Sales</span>
                    <span><span class="badge" style="background:#198754;">&nbsp;</span> Collection</span>
                </div>
            </div>
            <div class="card-body">
                <div id="chartSalesCollection" style="min-height:280px;"></div>
            </div>
        </div>
    </div>

    {{-- Expenses vs Payment --}}
    <div class="col-xl-6">
        <div class="card h-100 mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="header-title mb-0">Expenses vs Payment</h5>
                    <small class="text-muted">Last 6 months comparison</small>
                </div>
                <div class="d-flex gap-3 align-items-center" style="font-size:.78rem;">
                    <span><span class="badge" style="background:#fd7e14;">&nbsp;</span> Expenses</span>
                    <span><span class="badge" style="background:#6f42c1;">&nbsp;</span> Paid</span>
                </div>
            </div>
            <div class="card-body">
                <div id="chartExpensesPayment" style="min-height:280px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Secondary Stat Row ─────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-md-4 col-xl-3">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-medium mb-1" style="font-size:.8rem;">Total Revenue</p>
                        <h3 class="mb-0">{{ number_format($totalRevenue ?? 0, 2) }}</h3>
                    </div>
                    <div class="avatar-sm rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                        <i class="ti ti-currency-dollar fs-20"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-3">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-medium mb-1" style="font-size:.8rem;">Total Users</p>
                        <h3 class="mb-0">{{ $totalUsers ?? 0 }}</h3>
                    </div>
                    <div class="avatar-sm rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center">
                        <i class="ti ti-users fs-20"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-3">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-medium mb-1" style="font-size:.8rem;">Total Customers</p>
                        <h3 class="mb-0">{{ $totalCustomers ?? 0 }}</h3>
                    </div>
                    <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                        <i class="ti ti-building fs-20"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-3">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-medium mb-1" style="font-size:.8rem;">Total Staff</p>
                        <h3 class="mb-0">{{ $totalCustomers ?? 0 }}</h3>
                    </div>
                    <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                        <i class="ti ti-building fs-20"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Recent Invoices ────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="card mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="header-title mb-0">Recent Invoices</h4>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices ?? [] as $inv)
                            <tr>
                                <td><span class="fw-medium">{{ $inv->invoice_number }}</span></td>
                                <td>{{ $inv->customer->name ?? 'N/A' }}</td>
                                <td>
                                    @if($inv->payment_status == 'paid')
                                        <span class="badge bg-success-subtle text-success">Paid</span>
                                    @elseif($inv->payment_status == 'partial')
                                        <span class="badge bg-info-subtle text-info">Partial</span>
                                    @elseif($inv->status == 'cancelled')
                                        <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ number_format($inv->net_total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted">No recent invoices.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Low Stock (Admin View) --}}
    <div class="col-xl-5">
        <div class="card mb-0 h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 text-danger">Low Stock Alerts</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($lowStockProducts ?? [] as $prod)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">{{ $prod->name }}</h6>
                            <small class="text-muted">{{ $prod->sku }}</small>
                        </div>
                        <span class="badge bg-danger-subtle text-danger">Revise Stock</span>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center py-3">No low stock alerts.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@endif {{-- end $isAdmin --}}

{{-- ══════════════════════════════════════════════════════════════
     SALESPERSON SECTION
══════════════════════════════════════════════════════════════ --}}
@if($isSales && !$isAdmin)
<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1">Pending Quotes</p>
                        <h3 class="mb-0">{{ $pendingQuotes ?? 0 }}</h3>
                    </div>
                    <div class="avatar-sm rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                        <i class="ti ti-clock-hour-4 fs-20"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('quotations.index') }}" class="text-muted fs-13">View all pending <i class="ti ti-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1">Approved / Invoiced</p>
                        <h3 class="mb-0">{{ $approvedQuotes ?? 0 }}</h3>
                    </div>
                    <div class="avatar-sm rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                        <i class="ti ti-receipt fs-20"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('quotations.index') }}" class="text-muted fs-13">View details <i class="ti ti-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1">Create New</p>
                        <h5 class="mb-0 text-truncate">Sales Quotation</h5>
                    </div>
                    <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                        <i class="ti ti-plus fs-20"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('quotations.create') }}" class="btn btn-primary btn-sm w-100">Create Quotation</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="header-title mb-0">Recent Quotations</h4>
                <a href="{{ route('quotations.index') }}" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentQuotes ?? [] as $q)
                            <tr>
                                <td><span class="fw-medium">{{ $q->quotation_number }}</span></td>
                                <td>{{ $q->customer->name ?? 'N/A' }}</td>
                                <td>{{ $q->created_at->format('M d') }}</td>
                                <td>
                                    @php
                                        $statusClass = match($q->status) {
                                            'PENDING_ADMIN_APPROVAL' => 'bg-warning-subtle text-warning',
                                            'APPROVED_BY_ADMIN'      => 'bg-success-subtle text-success',
                                            'REJECTED'               => 'bg-danger-subtle text-danger',
                                            default                  => 'bg-secondary-subtle text-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }} py-1 px-2">{{ $q->status }}</span>
                                </td>
                                <td class="text-end fw-bold">{{ number_format($q->grand_total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-3 text-muted">No recent quotations.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     STOREPERSON SECTION (non-admin)
══════════════════════════════════════════════════════════════ --}}
@if($isStore && !$isAdmin)
<h5 class="mt-4 mb-3">Inventory Overview</h5>
<div class="row g-3">
    <div class="col-md-6 col-xl-3">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted fw-medium mb-1">Total Products</p>
                        <h3 class="mb-0">{{ $totalProducts ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card mb-0">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0 text-danger">Low Stock Alerts</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($lowStockProducts ?? [] as $prod)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">{{ $prod->name }}</h6>
                            <small class="text-muted">{{ $prod->sku }}</small>
                        </div>
                        <span class="badge bg-danger-subtle text-danger">Revise Stock</span>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center py-3">No low stock alerts.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if($isAdmin)
<script src="{{ asset('assets/plugins/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    // ── Shared config ─────────────────────────────────────────────
    var labels  = @json($chartLabels  ?? []);
    var sales   = @json($salesChart   ?? []);
    var colls   = @json($collectionsChart ?? []);
    var exps    = @json($expensesChart ?? []);
    var pays    = @json($paymentsChart ?? []);

    var commonOptions = {
        chart: { toolbar: { show: false }, height: 280, fontFamily: 'inherit' },
        dataLabels: { enabled: false },
        stroke: { width: [0, 3], curve: 'smooth' },
        xaxis: { categories: labels, labels: { style: { fontSize: '11px' } } },
        yaxis: { labels: { formatter: function(v){ return v.toLocaleString(undefined, {minimumFractionDigits:0, maximumFractionDigits:0}); } } },
        tooltip: { y: { formatter: function(v){ return v.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}); } } },
        legend: { show: false },
        grid: { borderColor: '#f0f0f0' },
    };

    // ── Chart 1: Sales vs Collection ──────────────────────────────
    new ApexCharts(document.getElementById('chartSalesCollection'), Object.assign({}, commonOptions, {
        chart: Object.assign({}, commonOptions.chart, { type: 'bar' }),
        series: [
            { name: 'Sales',      type: 'bar',  data: sales },
            { name: 'Collection', type: 'line', data: colls },
        ],
        colors: ['#0d6efd', '#198754'],
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
        fill: { opacity: [0.85, 1] },
    })).render();

    // ── Chart 2: Expenses vs Payment ──────────────────────────────
    new ApexCharts(document.getElementById('chartExpensesPayment'), Object.assign({}, commonOptions, {
        chart: Object.assign({}, commonOptions.chart, { type: 'bar' }),
        series: [
            { name: 'Expenses', type: 'bar',  data: exps },
            { name: 'Paid',     type: 'line', data: pays },
        ],
        colors: ['#fd7e14', '#6f42c1'],
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
        fill: { opacity: [0.85, 1] },
    })).render();
})();
</script>
@endif
@endpush
