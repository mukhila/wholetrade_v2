@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">GST Report — Supplier Payments</h4>
            <div class="d-flex gap-2 no-print">
                @if($records->count())
                {{-- Export dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="ti ti-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('supplier-reports.gst.export-csv', request()->query()) }}">
                                <i class="ti ti-file-spreadsheet me-2 text-success"></i> CSV — Detailed
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('supplier-reports.gst.export-monthly-csv', request()->query()) }}">
                                <i class="ti ti-file-spreadsheet me-2 text-success"></i> CSV — Month-wise
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- PDF / Print --}}
                <a href="{{ route('supplier-reports.gst.print', request()->query()) }}" target="_blank"
                   class="btn btn-outline-danger">
                    <i class="ti ti-file-type-pdf me-1"></i> PDF
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="ti ti-printer me-1"></i> Print
                </button>
                @endif
                <a href="{{ route('supplier-reports.outstanding') }}" class="btn btn-outline-secondary">
                    Outstanding Report
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Filter Panel --}}
<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Month</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">All Months</option>
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Year</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">
                    <i class="ti ti-filter me-1"></i>Filter
                </button>
                <a href="{{ route('supplier-reports.gst') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

@if(request()->hasAny(['supplier_id','month','year']))
<div class="no-print mb-2">
    <small class="text-muted">
        Filtered:
        @if(request('supplier_id'))
            Supplier: <strong>{{ $suppliers->find(request('supplier_id'))?->name }}</strong>
        @endif
        @if(request('month'))
            Month: <strong>{{ DateTime::createFromFormat('!m', request('month'))->format('F') }}</strong>
        @endif
        @if(request('year'))
            Year: <strong>{{ request('year') }}</strong>
        @endif
    </small>
</div>
@endif

{{-- Summary Cards --}}
@if($records->count())
<div class="row mb-3 no-print">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body py-2 px-3">
                <div class="text-muted small">Total Invoices</div>
                <div class="fs-4 fw-bold text-primary">{{ $totals['count'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-secondary">
            <div class="card-body py-2 px-3">
                <div class="text-muted small">Total Purchase Amount</div>
                <div class="fs-5 fw-bold">{{ number_format($totals['purchase_amount'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body py-2 px-3">
                <div class="text-muted small">Total GST Amount</div>
                <div class="fs-5 fw-bold text-warning">{{ number_format($totals['gst_amount'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body py-2 px-3">
                <div class="text-muted small">Net Amount (excl. GST)</div>
                <div class="fs-5 fw-bold text-success">{{ number_format($totals['net_amount'], 2) }}</div>
            </div>
        </div>
    </div>
</div>
@endif

<div id="print-area">

    {{-- Print Header --}}
    <div class="d-none d-print-block mb-3">
        <div class="text-center">
            <div style="font-size:16px; font-weight:bold; text-transform:uppercase;">
                {{ $companySetting->company_name ?? '' }}
            </div>
            @if($companySetting->address ?? null)
                <div style="font-size:11px;">{{ $companySetting->address }}</div>
            @endif
            @if($companySetting->phone_no ?? null)
                <div style="font-size:11px;">Tel: {{ $companySetting->phone_no }}</div>
            @endif
            @if($companySetting->uen_no ?? null)
                <div style="font-size:11px;">UEN: {{ $companySetting->uen_no }}</div>
            @endif
        </div>
        <hr style="border-top:2px solid #000; margin:6px 0;">
        <div class="text-center" style="font-size:14px; font-weight:bold; letter-spacing:1px;">
            GST REPORT — SUPPLIER PAYMENTS
        </div>
        <div class="d-flex justify-content-between mt-1" style="font-size:10px;">
            <span>
                @if(request('month') || request('year'))
                    Period:
                    @if(request('month')) {{ DateTime::createFromFormat('!m', request('month'))->format('F') }} @endif
                    @if(request('year')) {{ request('year') }} @endif
                @else
                    Period: All
                @endif
                @if(request('supplier_id'))
                    &nbsp;|&nbsp; Supplier: {{ $suppliers->find(request('supplier_id'))?->name }}
                @endif
            </span>
            <span>Printed: {{ now()->format('d M Y, h:i A') }}</span>
        </div>
        <hr style="border-top:1px solid #000; margin:4px 0 8px;">
    </div>

    {{-- Tab Toggle (screen only) --}}
    <ul class="nav nav-tabs mb-3 no-print" id="gstTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-detailed">Detailed Report</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-monthly">Month-wise Summary</a>
        </li>
    </ul>

    <div class="tab-content">

        {{-- ── DETAILED REPORT ── --}}
        <div class="tab-pane fade show active" id="tab-detailed">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="gstDetailTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>No.</th>
                                    <th>Invoice No.</th>
                                    <th>Date</th>
                                    <th>Supplier Name</th>
                                    <th class="text-end">Invoice Amount</th>
                                    <th class="text-center">GST Rate</th>
                                    <th class="text-end">GST Amount</th>
                                    <th class="text-end">Net (excl. GST)</th>
                                    <th class="text-center no-print">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $i => $row)
                                @php $net = $row->purchase_amount - $row->gst_amount; @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->invoice_number }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row->purchase_date)->format('d M Y') }}</td>
                                    <td>{{ $row->supplier_name }}</td>
                                    <td class="text-end">{{ number_format($row->purchase_amount, 2) }}</td>
                                    <td class="text-center">
                                        @if($row->gst_rate > 0)
                                            <span class="badge bg-info text-dark">{{ number_format($row->gst_rate, 0) }}%</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-medium {{ $row->gst_amount > 0 ? 'text-warning' : 'text-muted' }}">
                                        {{ number_format($row->gst_amount, 2) }}
                                    </td>
                                    <td class="text-end text-success">{{ number_format($net, 2) }}</td>
                                    <td class="text-center no-print">
                                        @if($row->payment_status === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($row->payment_status === 'partial')
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="ti ti-file-search fs-3 d-block mb-2"></i>
                                        No records found. Use the filter above to search.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($records->count() > 0)
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="4" class="fw-bold">Grand Total ({{ $totals['count'] }} invoices)</td>
                                    <td class="text-end fw-bold">{{ number_format($totals['purchase_amount'], 2) }}</td>
                                    <td></td>
                                    <td class="text-end fw-bold text-warning">{{ number_format($totals['gst_amount'], 2) }}</td>
                                    <td class="text-end fw-bold text-success">{{ number_format($totals['net_amount'], 2) }}</td>
                                    <td class="no-print"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── MONTH-WISE SUMMARY ── --}}
        <div class="tab-pane fade" id="tab-monthly">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="gstMonthTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>No.</th>
                                    <th>Month</th>
                                    <th class="text-center">Total Invoices</th>
                                    <th class="text-end">Total Purchase Amount</th>
                                    <th class="text-end">Total GST Amount</th>
                                    <th class="text-end">Net Amount (excl. GST)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monthSummary as $i => $row)
                                @php
                                    $monthLabel = DateTime::createFromFormat('!m', $row->mo)->format('F') . ' ' . $row->yr;
                                    $net = $row->total_amount - $row->total_gst;
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $monthLabel }}</td>
                                    <td class="text-center">{{ $row->total_invoices }}</td>
                                    <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                                    <td class="text-end fw-medium text-warning">{{ number_format($row->total_gst, 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($net, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="ti ti-calendar-off fs-3 d-block mb-2"></i>
                                        No records found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($monthSummary->count() > 0)
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="2" class="fw-bold">Grand Total</td>
                                    <td class="text-center fw-bold">{{ $monthSummary->sum('total_invoices') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($monthSummary->sum('total_amount'), 2) }}</td>
                                    <td class="text-end fw-bold text-warning">{{ number_format($monthSummary->sum('total_gst'), 2) }}</td>
                                    <td class="text-end fw-bold text-success">
                                        {{ number_format($monthSummary->sum('total_amount') - $monthSummary->sum('total_gst'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /.tab-content --}}

    {{-- Print-only Month Summary (always shown when printing) --}}
    @if($monthSummary->count() > 1)
    <div class="d-none d-print-block mt-4">
        <div style="font-size:12px; font-weight:bold; margin-bottom:6px; text-transform:uppercase; border-bottom:1px solid #000; padding-bottom:4px;">
            Month-wise GST Summary
        </div>
        <table style="width:100%; border-collapse:collapse; font-size:11px;">
            <thead>
                <tr style="background:#343a40; color:#fff;">
                    <th style="padding:4px 6px; text-align:left;">Month</th>
                    <th style="padding:4px 6px; text-align:center;">Invoices</th>
                    <th style="padding:4px 6px; text-align:right;">Total Amount</th>
                    <th style="padding:4px 6px; text-align:right;">GST Amount</th>
                    <th style="padding:4px 6px; text-align:right;">Net Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthSummary as $row)
                @php $net = $row->total_amount - $row->total_gst; @endphp
                <tr style="border-bottom:1px solid #dee2e6;">
                    <td style="padding:3px 6px;">{{ DateTime::createFromFormat('!m', $row->mo)->format('F') }} {{ $row->yr }}</td>
                    <td style="padding:3px 6px; text-align:center;">{{ $row->total_invoices }}</td>
                    <td style="padding:3px 6px; text-align:right;">{{ number_format($row->total_amount, 2) }}</td>
                    <td style="padding:3px 6px; text-align:right;">{{ number_format($row->total_gst, 2) }}</td>
                    <td style="padding:3px 6px; text-align:right;">{{ number_format($net, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#e9ecef; font-weight:bold;">
                    <td style="padding:4px 6px;">Grand Total</td>
                    <td style="padding:4px 6px; text-align:center;">{{ $monthSummary->sum('total_invoices') }}</td>
                    <td style="padding:4px 6px; text-align:right;">{{ number_format($monthSummary->sum('total_amount'), 2) }}</td>
                    <td style="padding:4px 6px; text-align:right;">{{ number_format($monthSummary->sum('total_gst'), 2) }}</td>
                    <td style="padding:4px 6px; text-align:right;">
                        {{ number_format($monthSummary->sum('total_amount') - $monthSummary->sum('total_gst'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

</div>{{-- /#print-area --}}

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .tab-pane { display: block !important; opacity: 1 !important; }
    #tab-monthly { display: none !important; }
    .table-dark th { background-color: #343a40 !important; color: #fff !important; }
    .text-warning  { color: #e6a817 !important; }
    .text-success  { color: #198754 !important; }
    .text-muted    { color: #6c757d !important; }
    .table-secondary { background-color: #e9ecef !important; }
    .badge.bg-info    { background-color: #0dcaf0 !important; color: #000 !important; }
    .badge.bg-success { background-color: #198754 !important; color: #fff !important; }
    .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
    .badge.bg-danger  { background-color: #dc3545 !important; color: #fff !important; }
    #print-area .card { border: none !important; box-shadow: none !important; }
    #print-area .card-body { padding: 0 !important; }
    .nav-tabs { display: none !important; }
}
</style>
@endpush
@endsection
