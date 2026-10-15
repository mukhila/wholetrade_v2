@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Customer-wise Report</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Whole Trade</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Customer-wise</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Filter Card --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2">
                <h5 class="card-title mb-0"><i class="ti ti-filter me-1"></i> Filter Options</h5>
            </div>
            <div class="card-body py-3">
                <form action="{{ route('reports.customers.index') }}" method="GET" id="filterForm">
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-2">
                            <label class="form-label mb-1 small fw-medium">From Date</label>
                            <input type="date" class="form-control form-control-sm"
                                   name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label mb-1 small fw-medium">To Date</label>
                            <input type="date" class="form-control form-control-sm"
                                   name="date_to" value="{{ request('date_to') }}">
                        </div>

                        {{-- Searchable Customer Select --}}
                        <div class="col-sm-3">
                            <label class="form-label mb-1 small fw-medium">Customer</label>
                            <div class="position-relative" id="customer_search_wrapper">
                                <input type="text"
                                       id="customer_search"
                                       class="form-control form-control-sm"
                                       placeholder="Search customer..."
                                       autocomplete="off"
                                       value="{{ request('customer_id') ? ($customers->firstWhere('id', request('customer_id'))?->name ?? '') : '' }}">
                                <input type="hidden" name="customer_id" id="customer_id_input" value="{{ request('customer_id') }}">
                                <div id="customer_dropdown"
                                     class="position-absolute w-100 bg-white border rounded shadow-sm"
                                     style="z-index:1050;max-height:220px;overflow-y:auto;display:none;top:100%;left:0;">
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-2">
                            <label class="form-label mb-1 small fw-medium">Payment Status</label>
                            <select class="form-select form-select-sm" name="payment_status">
                                <option value="">All</option>
                                <option value="unpaid"  {{ request('payment_status') === 'unpaid'  ? 'selected' : '' }}>Unpaid</option>
                                <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="paid"    {{ request('payment_status') === 'paid'    ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>
                        <div class="col-sm-3 d-flex gap-1 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ti ti-search me-1"></i> Search
                            </button>
                            @if(request()->hasAny(['date_from','date_to','customer_id','payment_status']))
                                <a href="{{ route('reports.customers.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="ti ti-x me-1"></i> Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Export / Print --}}
<div class="row mb-2">
    <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="{{ route('reports.customers.print-summary', request()->all()) }}" target="_blank"
           class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-printer me-1"></i> Print Summary
        </a>
        <button type="button" class="btn btn-sm btn-outline-success" id="btnPageThermalPrint">
            <i class="ti ti-receipt me-1"></i> Thermal Print
        </button>
        <a href="{{ route('reports.customers.export', array_merge(request()->all(), ['type'=>'summary'])) }}"
           class="btn btn-sm btn-outline-success">
            <i class="ti ti-download me-1"></i> Export CSV
        </a>
    </div>
</div>

{{-- Summary Table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="ti ti-chart-bar me-1"></i> Customer Summary
                    @if($summary->isNotEmpty())
                        <span class="badge bg-primary ms-1">{{ $summary->count() }}</span>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                @if($summary->isNotEmpty())

                {{-- Grand totals strip --}}
                <div class="row g-3 mb-3">
                    <div class="col-6 col-sm-3">
                        <div class="border rounded p-2 text-center bg-light">
                            <div class="small text-muted">Customers</div>
                            <div class="fw-bold fs-5">{{ $summary->count() }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="border rounded p-2 text-center bg-light">
                            <div class="small text-muted">Total Invoices</div>
                            <div class="fw-bold fs-5">{{ $summary->sum('invoice_count') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="border rounded p-2 text-center bg-success bg-opacity-10">
                            <div class="small text-muted">Grand Total Sales</div>
                            <div class="fw-bold fs-6 text-success">{{ number_format($summary->sum('total_sales'), 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="border rounded p-2 text-center bg-danger bg-opacity-10">
                            <div class="small text-muted">Grand Balance Due</div>
                            <div class="fw-bold fs-6 text-danger">{{ number_format($summary->sum('total_balance'), 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-end">Total Sales</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Balance Due</th>
                                <th class="text-center">Paid %</th>
                                <th class="text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary as $i => $row)
                            @php
                                $paidPct = $row['total_sales'] > 0 ? ($row['total_paid'] / $row['total_sales']) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <span class="fw-medium text-primary" style="cursor:pointer;"
                                          onclick="showCustomerDetail({{ $row['customer_id'] }}, '{{ addslashes($row['customer_name']) }}')">
                                        {{ $row['customer_name'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $row['invoice_count'] }}</span>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($row['total_sales'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($row['total_paid'], 2) }}</td>
                                <td class="text-end {{ $row['total_balance'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    {{ number_format($row['total_balance'], 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress flex-grow-1" style="height:6px;">
                                            <div class="progress-bar bg-success" style="width:{{ min(100, $paidPct) }}%"></div>
                                        </div>
                                        <span class="small text-muted">{{ number_format($paidPct, 0) }}%</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-xs btn-soft-primary"
                                            onclick="showCustomerDetail({{ $row['customer_id'] }}, '{{ addslashes($row['customer_name']) }}')"
                                            title="View invoices">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2">Grand Total</td>
                                <td class="text-center">{{ $summary->sum('invoice_count') }}</td>
                                <td class="text-end">{{ number_format($summary->sum('total_sales'), 2) }}</td>
                                <td class="text-end text-success">{{ number_format($summary->sum('total_paid'), 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($summary->sum('total_balance'), 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @else
                <div class="text-center text-muted py-5">
                    <i class="ti ti-users" style="font-size:3rem;opacity:.3;"></i>
                    <p class="mt-3 mb-0">No customer data found for the selected filters.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Customer Detail Modal --}}
<div class="modal fade" id="customerDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-user me-1 text-primary"></i>
                    <span id="customerDetailTitle">Customer Detail</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="customerDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="customerDetailFooter" style="display:none!important;">
                <div class="d-flex gap-4 fw-bold small me-auto">
                    <span>Grand Total:</span>
                    <span class="text-primary" id="modalTotalSales"></span>
                    <span class="text-success" id="modalTotalPaid"></span>
                    <span class="text-danger" id="modalTotalBalance"></span>
                </div>
                <a id="btnModalNormalPrint" href="#" target="_blank"
                   class="btn btn-sm btn-outline-dark">
                    <i class="ti ti-printer me-1"></i> Normal Print
                </a>
                <button id="btnModalThermalPrint" type="button"
                        class="btn btn-sm btn-outline-success">
                    <i class="ti ti-receipt me-1"></i> Thermal Print
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Searchable Customer Select ────────────────────────────────────────────────
(function () {
    const customers = @json($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values());
    const searchInput  = document.getElementById('customer_search');
    const hiddenInput  = document.getElementById('customer_id_input');
    const dropdown     = document.getElementById('customer_dropdown');
    const wrapper      = document.getElementById('customer_search_wrapper');

    function renderDropdown(items) {
        dropdown.innerHTML = '';

        const allItem = document.createElement('div');
        allItem.className = 'px-3 py-2 border-bottom text-muted small';
        allItem.style.cursor = 'pointer';
        allItem.textContent = 'All Customers';
        allItem.addEventListener('mousedown', function (e) {
            e.preventDefault();
            searchInput.value = '';
            hiddenInput.value = '';
            dropdown.style.display = 'none';
        });
        dropdown.appendChild(allItem);

        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-muted small';
            empty.textContent = 'No customers found';
            dropdown.appendChild(empty);
        } else {
            items.forEach(function (c) {
                const item = document.createElement('div');
                item.className = 'px-3 py-2';
                item.style.cursor = 'pointer';
                item.textContent = c.name;
                item.addEventListener('mouseover', function () { this.style.background = '#f0f4ff'; });
                item.addEventListener('mouseout',  function () { this.style.background = ''; });
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    searchInput.value = c.name;
                    hiddenInput.value = c.id;
                    dropdown.style.display = 'none';
                });
                dropdown.appendChild(item);
            });
        }

        dropdown.style.display = 'block';
    }

    searchInput.addEventListener('focus', function () {
        const term = this.value.toLowerCase();
        renderDropdown(customers.filter(c => c.name.toLowerCase().includes(term)));
    });

    searchInput.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        hiddenInput.value = '';
        renderDropdown(customers.filter(c => c.name.toLowerCase().includes(term)));
    });

    searchInput.addEventListener('blur', function () {
        setTimeout(() => { dropdown.style.display = 'none'; }, 150);
        // If the typed text doesn't match any customer, clear the hidden input
        const match = customers.find(c => c.name.toLowerCase() === this.value.toLowerCase().trim());
        if (match) {
            hiddenInput.value = match.id;
            searchInput.value = match.name;
        } else if (!hiddenInput.value) {
            searchInput.value = '';
        }
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
})();

// ── Report Thermal Print (mirrors window.printViaBle used in invoice show page) ──
var currentModalFilters = {};

document.getElementById('btnPageThermalPrint').addEventListener('click', function () {
    var urlParams = new URLSearchParams(window.location.search);
    var filters = {};
    ['date_from', 'date_to', 'customer_id', 'payment_status'].forEach(function (k) {
        var v = urlParams.get(k);
        if (v) filters[k] = v;
    });
    printReportViaBle(filters);
});

document.getElementById('btnModalThermalPrint').addEventListener('click', function () {
    printReportViaBle(currentModalFilters);
});

async function printReportViaBle(filters) {
    // Desktop/iOS: open the thermal print page in a new tab — it auto-triggers window.print()
    if (!/android/i.test(navigator.userAgent || '')) {
        var baseUrl = window.APP_URL || (window.location.origin + '/');
        if (!baseUrl.endsWith('/')) baseUrl += '/';
        var qs = Object.keys(filters).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(filters[k]);
        }).join('&');
        window.open(baseUrl + 'reports/customers/print-detailed-thermal' + (qs ? '?' + qs : ''), '_blank');
        return;
    }

    // Android: send to print app via PrintBridge
    var savedMm = 80;
    try { savedMm = parseInt(localStorage.getItem('tp_paper_mm')) || 80; } catch (e) {}
    var baseUrl = window.APP_URL || (window.location.origin + '/');
    if (!baseUrl.endsWith('/')) baseUrl += '/';
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    try {
        if (typeof showToast === 'function') showToast('Sending report to print app…', 'info');

        const resp = await fetch(baseUrl + 'reports/customers/direct-print-thermal', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ format: 'escpos', station: 'main', width: savedMm, copies: 1, filters: filters })
        });
        const d = await resp.json();
        if (!d.success) throw new Error(d.error || 'Could not queue print job');

        var fbUrl = baseUrl + 'reports/customers/print-detailed-thermal';
        var q = 'job_id='    + encodeURIComponent(d.job_id) +
                '&invoice_id=0' +
                '&server_url=' + encodeURIComponent(baseUrl.replace(/\/$/, '')) +
                '&width='      + encodeURIComponent(savedMm) +
                '&t='          + Date.now();
        window.location.href = 'intent://preview?' + q +
            '#Intent;scheme=wholetradeprint;package=com.techgeeks.wholetradeprint;' +
            'S.browser_fallback_url=' + encodeURIComponent(fbUrl) + ';end';
    } catch (err) {
        if (typeof showToast === 'function') showToast('Print failed: ' + err.message, 'error');
    }
}

// ── Customer Detail Modal ────────────────────────────────────────────────────
function showCustomerDetail(customerId, customerName) {
    document.getElementById('customerDetailTitle').textContent = customerName;
    document.getElementById('customerDetailBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted small">Loading invoices…</p></div>';
    document.getElementById('customerDetailFooter').style.display = 'none';

    const modal = new bootstrap.Modal(document.getElementById('customerDetailModal'));
    modal.show();

    const params = new URLSearchParams(window.location.search);
    params.set('customer_id', customerId);

    // Wire up normal print link immediately
    document.getElementById('btnModalNormalPrint').href = '{{ route("reports.customers.print-detailed") }}?' + params.toString();

    // Capture filters for thermal print
    currentModalFilters = { customer_id: String(customerId) };
    ['date_from', 'date_to', 'payment_status'].forEach(function (k) {
        var v = params.get(k);
        if (v) currentModalFilters[k] = v;
    });

    fetch('/api/reports/customers/detail?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        const invoices = data.invoices;

        if (!invoices || invoices.length === 0) {
            document.getElementById('customerDetailBody').innerHTML =
                '<div class="text-center text-muted py-4">No invoices found for this customer.</div>';
            return;
        }

        const fmt = v => parseFloat(v || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        let rows = invoices.map(function (inv) {
            const statusColor = inv.payment_status === 'paid' ? 'success' : (inv.payment_status === 'partial' ? 'warning' : 'danger');
            const balClass    = parseFloat(inv.balance_due) > 0 ? 'text-danger fw-bold' : 'text-muted';
            const date        = inv.invoice_date ? new Date(inv.invoice_date).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '-';
            return `<tr>
                <td class="fw-medium">${inv.invoice_number || '-'}</td>
                <td>${date}</td>
                <td class="text-end">${fmt(inv.net_total)}</td>
                <td class="text-end text-success">${fmt(inv.paid_amount)}</td>
                <td class="text-end ${balClass}">${fmt(inv.balance_due)}</td>
                <td><span class="badge bg-${statusColor}">${inv.payment_status ? inv.payment_status.charAt(0).toUpperCase() + inv.payment_status.slice(1) : '-'}</span></td>
                <td><span class="badge bg-secondary">${inv.status ? inv.status.charAt(0).toUpperCase() + inv.status.slice(1) : '-'}</span></td>
            </tr>`;
        }).join('');

        const t = data.totals;
        document.getElementById('customerDetailBody').innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Payment Status</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td colspan="2" class="text-end">Subtotal</td>
                            <td class="text-end">${fmt(t.net_total)}</td>
                            <td class="text-end text-success">${fmt(t.paid_amount)}</td>
                            <td class="text-end text-danger">${fmt(t.balance_due)}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>`;

        const footer = document.getElementById('customerDetailFooter');
        document.getElementById('modalTotalSales').textContent   = 'Sales: '   + fmt(t.net_total);
        document.getElementById('modalTotalPaid').textContent    = 'Paid: '    + fmt(t.paid_amount);
        document.getElementById('modalTotalBalance').textContent = 'Balance: ' + fmt(t.balance_due);
        footer.style.removeProperty('display');
    })
    .catch(function () {
        document.getElementById('customerDetailBody').innerHTML =
            '<div class="alert alert-danger">Failed to load customer detail. Please try again.</div>';
    });
}
</script>
@endpush
@endsection
