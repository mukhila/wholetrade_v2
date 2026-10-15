@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Sales Invoices</h4>
            <div class="page-title-right d-flex gap-2 align-items-center">
                @if($canExport)
                <a href="{{ route('sales-invoices.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="ti ti-download me-1"></i> Export CSV
                </a>
                @endif
                @if($canCreate)
                <div class="btn-group">
                    <a href="{{ route('sales-invoices.create') }}?invoice_type=gst" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> GST Invoice
                    </a>
                    <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('sales-invoices.create') }}?invoice_type=gst">
                                <i class="ti ti-receipt-tax me-2 text-primary"></i> GST Invoice
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('sales-invoices.create') }}?invoice_type=non_gst">
                                <i class="ti ti-receipt me-2 text-secondary"></i> Without GST Invoice
                            </a>
                        </li>
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-3">
                <form action="{{ route('sales-invoices.index') }}" method="GET" id="filterForm">
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
                        <div class="col-sm-3">
                            <label class="form-label mb-1 small fw-medium">Customer</label>
                            <select class="form-select form-select-sm" name="customer_id">
                                <option value="">All Customers</option>
                                @foreach($customers as $cust)
                                    <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>
                                        {{ $cust->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label mb-1 small fw-medium">Invoice #</label>
                            <input type="text" class="form-control form-control-sm"
                                   name="search" value="{{ request('search') }}"
                                   placeholder="Search invoice number...">
                        </div>
                        <div class="col-sm-2 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="ti ti-search me-1"></i> Search
                            </button>
                            @if(request()->hasAny(['date_from','date_to','customer_id','search']))
                                <a href="{{ route('sales-invoices.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="ti ti-x"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Tabs (only show Summary tab when date filter is active) --}}
@php $hasFilter = request()->hasAny(['date_from','date_to','customer_id','search']); @endphp
@php $activeTab = request('tab', 'details'); @endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs nav-tabs-custom" id="invoiceTabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab !== 'summary' ? 'active' : '' }}"
                           href="#"
                           onclick="switchTab('details', this); return false;">
                            <i class="ti ti-list me-1"></i> Sales Details
                        </a>
                    </li>
                    @if($hasFilter)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'summary' ? 'active' : '' }}"
                           href="#"
                           onclick="switchTab('summary', this); return false;">
                            <i class="ti ti-chart-bar me-1"></i> Date-wise Summary
                            @if($summary->isNotEmpty())
                                <span class="badge bg-primary ms-1">{{ $summary->count() }} day(s)</span>
                            @endif
                        </a>
                    </li>
                    @endif
                </ul>
            </div>

            {{-- Sales Details Tab --}}
            <div id="tab-details" class="{{ $activeTab === 'summary' ? 'd-none' : '' }}">
                <div class="card-body">
                    {{-- KPI strip when filter is active --}}
                    @if($hasFilter && $invoices->total() > 0)
                    <div class="row g-3 mb-3">
                        @php
                            $pageTotalQty = $invoices->sum(fn($i) => $i->total_quantity ?? 0);
                            $totalSales   = $invoices->sum('net_total');
                            $totalGst     = $invoices->sum('tax_amount');
                            $totalPaid    = $invoices->sum('paid_amount');
                            $totalBalance = $invoices->sum('balance_due');
                        @endphp
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-light">
                                <div class="small text-muted">Invoices (this page)</div>
                                <div class="fw-bold">{{ $invoices->count() }} / {{ $invoices->total() }}</div>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-light">
                                <div class="small text-muted">Page Total Qty</div>
                                <div class="fw-bold text-dark">{{ (float)$pageTotalQty == intval($pageTotalQty) ? number_format($pageTotalQty, 0) : number_format($pageTotalQty, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="border rounded p-2 text-center bg-light">
                                <div class="small text-muted">Page Total Sales</div>
                                <div class="fw-bold text-primary">{{ number_format($totalSales, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-light">
                                <div class="small text-muted">Page Total GST</div>
                                <div class="fw-bold text-info">{{ number_format($totalGst, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="border rounded p-2 text-center bg-light">
                                <div class="small text-muted">Page Balance Due</div>
                                <div class="fw-bold text-danger">{{ number_format($totalBalance, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">GST</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $inv)
                                <tr id="invoice-{{ $inv->id }}" @if($inv->status === 'voided') style="opacity:0.55;text-decoration:line-through" @endif>
                                    <td class="fw-medium">{{ $inv->invoice_number }}</td>
                                    <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</td>
                                    <td>{{ $inv->customer->name ?? 'N/A' }}</td>
                                    <td class="text-center fw-semibold">
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            {{ (float)($inv->total_quantity ?? 0) == intval($inv->total_quantity ?? 0) ? number_format($inv->total_quantity ?? 0, 0) : number_format($inv->total_quantity ?? 0, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($inv->net_total, 2) }}</td>
                                    <td class="text-end text-info">{{ number_format($inv->tax_amount, 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($inv->paid_amount, 2) }}</td>
                                    <td class="text-end fw-bold text-danger">{{ number_format($inv->balance_due, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $inv->payment_status == 'paid' ? 'success' : ($inv->payment_status == 'partial' ? 'warning' : ($inv->payment_status == 'voided' ? 'dark' : 'danger')) }}">
                                            {{ ucfirst($inv->payment_status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @include('partials.status_badge', ['status' => $inv->status])
                                    </td>
                                    <td>
                                        <a href="{{ route('sales-invoices.show', ['sales_invoice' => $inv->id, 'return_url' => request()->fullUrl() . '#invoice-' . $inv->id]) }}" class="btn btn-sm btn-soft-primary" title="View">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        @if($inv->status === 'draft' && $canEdit)
                                        <a href="{{ route('sales-invoices.edit', $inv->id) }}" class="btn btn-sm btn-soft-warning" title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        @endif
                                        @if(in_array($inv->status, ['confirmed', 'posted']))
                                        @if($inv->balance_due > 0)
                                        <a href="{{ route('sales-invoices.pay', ['id' => $inv->id, 'return_url' => request()->fullUrl() . '#invoice-' . $inv->id]) }}" class="btn btn-sm btn-soft-success" title="Record Payment">
                                            <i class="ti ti-cash"></i> Pay
                                        </a>
                                        @endif
                                        @endif
                                        @if($inv->status === 'voided')
                                        <span class="badge bg-dark">VOIDED</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">
                                        @if($hasFilter)
                                            No invoices found for the selected filters.
                                        @else
                                            No invoices yet.
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>

            {{-- Date-wise Summary Tab --}}
            @if($hasFilter)
            <div id="tab-summary" class="{{ $activeTab !== 'summary' ? 'd-none' : '' }}">
                <div class="card-body">
                    @if($summary->isNotEmpty())
                    {{-- Grand totals strip --}}
                    @php $grandSummaryQty = $summary->sum('total_qty'); @endphp
                    <div class="row g-3 mb-3">
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-primary bg-opacity-10">
                                <div class="small text-muted">Total Days</div>
                                <div class="fw-bold">{{ $summary->count() }}</div>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-primary bg-opacity-10">
                                <div class="small text-muted">Total Invoices</div>
                                <div class="fw-bold">{{ $summary->sum('invoice_count') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-secondary bg-opacity-10">
                                <div class="small text-muted">Grand Total Qty</div>
                                <div class="fw-bold text-dark">{{ (float)$grandSummaryQty == intval($grandSummaryQty) ? number_format($grandSummaryQty, 0) : number_format($grandSummaryQty, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-success bg-opacity-10">
                                <div class="small text-muted">Grand Total Sales</div>
                                <div class="fw-bold text-success">{{ number_format($summary->sum('total_sales'), 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-info bg-opacity-10">
                                <div class="small text-muted">Grand Total GST</div>
                                <div class="fw-bold text-info">{{ number_format($summary->sum('total_gst'), 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="border rounded p-2 text-center bg-danger bg-opacity-10">
                                <div class="small text-muted">Grand Total Balance</div>
                                <div class="fw-bold text-danger">{{ number_format($summary->sum('total_balance'), 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th class="text-center">Invoices</th>
                                    <th class="text-center">Total Qty</th>
                                    <th class="text-end">Total Sales</th>
                                    <th class="text-end">Total GST</th>
                                    <th class="text-end">Total Paid</th>
                                    <th class="text-end">Balance Due</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary as $row)
                                <tr>
                                    <td class="fw-medium">
                                        {{ \Carbon\Carbon::parse($row->date)->format('d M Y (D)') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $row->invoice_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            {{ (float)($row->total_qty ?? 0) == intval($row->total_qty ?? 0) ? number_format($row->total_qty ?? 0, 0) : number_format($row->total_qty ?? 0, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format($row->total_sales, 2) }}</td>
                                    <td class="text-end text-info">{{ number_format($row->total_gst, 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($row->total_paid, 2) }}</td>
                                    <td class="text-end {{ $row->total_balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                        {{ number_format($row->total_balance, 2) }}
                                    </td>
                                    <td class="text-center">
                                        {{-- Link to detail view filtered to just this day --}}
                                        <a href="{{ route('sales-invoices.index', array_merge(request()->except('date_from','date_to','tab'), ['date_from' => $row->date, 'date_to' => $row->date])) }}"
                                           class="btn btn-xs btn-soft-primary" title="View invoices for this day">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>Grand Total</td>
                                    <td class="text-center">{{ $summary->sum('invoice_count') }}</td>
                                    <td class="text-center">
                                        {{ (float)$grandSummaryQty == intval($grandSummaryQty) ? number_format($grandSummaryQty, 0) : number_format($grandSummaryQty, 2) }}
                                    </td>
                                    <td class="text-end">{{ number_format($summary->sum('total_sales'), 2) }}</td>
                                    <td class="text-end text-info">{{ number_format($summary->sum('total_gst'), 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($summary->sum('total_paid'), 2) }}</td>
                                    <td class="text-end text-danger">{{ number_format($summary->sum('total_balance'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        No invoices found for the selected date range.
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function switchTab(tab, el) {
    document.querySelectorAll('#invoiceTabs .nav-link').forEach(a => a.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('tab-details').classList.add('d-none');
    var summaryEl = document.getElementById('tab-summary');
    if (summaryEl) summaryEl.classList.add('d-none');
    document.getElementById('tab-' + tab).classList.remove('d-none');
}
</script>
@endpush

@push('styles')
<style>
tr:target {
    scroll-margin-top: 150px;
}
tr:target td {
    animation: highlight-row 2s ease-out;
}
@keyframes highlight-row {
    0% {
        background-color: rgba(138, 184, 68, 0.3) !important;
    }
    100% {
        background-color: transparent;
    }
}
</style>
@endpush
@endsection
