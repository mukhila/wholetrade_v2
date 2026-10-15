@extends('layouts.app')

@section('content')
<div class="row mt-2 no-print">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">GIRO Payment Report</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('supplier-reports.giro.export', request()->only(['supplier_id','show_paid'])) }}"
                   class="btn btn-outline-success">
                    <i class="ti ti-file-download me-1"></i> Export CSV
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="ti ti-printer me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show no-print">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Filters (screen only) --}}
<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="show_paid" value="1" id="showPaid"
                        {{ request('show_paid') ? 'checked' : '' }}>
                    <label class="form-check-label" for="showPaid">Show Paid GIRO</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div id="print-area">

    {{-- Print-only header --}}
    <div class="d-none d-print-block mb-3">
        @php $cs = \App\Models\CompanySetting::first(); @endphp
        <div class="text-center">
            <div style="font-size:16px; font-weight:bold; text-transform:uppercase;">
                {{ $cs->company_name ?? '' }}
            </div>
            @if($cs->address ?? null)
                <div style="font-size:11px;">{{ $cs->address }}</div>
            @endif
            @if($cs->phone_no ?? null)
                <div style="font-size:11px;">Tel: {{ $cs->phone_no }}</div>
            @endif
        </div>
        <hr style="border-top:2px solid #000; margin:6px 0;">
        <div class="text-center" style="font-size:14px; font-weight:bold; letter-spacing:1px;">
            GIRO PAYMENT REPORT
        </div>
        <div class="text-end" style="font-size:10px; margin-top:2px;">
            Printed: {{ now()->format('d M Y, h:i A') }}
            @if(request('supplier_id'))
                @php $sel = $suppliers->firstWhere('id', request('supplier_id')); @endphp
                @if($sel) &nbsp;|&nbsp; Supplier: {{ $sel->name }} @endif
            @endif
            @if(request('show_paid')) &nbsp;|&nbsp; Including Paid @endif
        </div>
        <hr style="border-top:1px solid #000; margin:4px 0 8px;">
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Company Name</th>
                            <th>Payment Ref</th>
                            <th class="text-end">Amount</th>
                            <th>GIRO Date</th>
                            <th>Invoices</th>
                            <th class="text-center">Status</th>
                            <th class="text-center no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $i => $p)
                        <tr class="{{ $p->giro_is_paid ? 'opacity-50' : '' }}">
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $p->supplier->name ?? '—' }}</td>
                            <td>{{ $p->reference_number ?? '—' }}</td>
                            <td class="text-end fw-bold">{{ number_format($p->total_amount, 2) }}</td>
                            <td>
                                @if($p->giro_date)
                                    <span class="{{ !$p->giro_is_paid && $p->giro_date->isPast() ? 'text-danger fw-bold' : '' }}">
                                        {{ $p->giro_date->format('d M Y') }}
                                    </span>
                                    @if(!$p->giro_is_paid && $p->giro_date->isToday())
                                        <span class="badge bg-warning text-dark ms-1">Today</span>
                                    @elseif(!$p->giro_is_paid && $p->giro_date->isPast())
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @foreach($p->items as $item)
                                    <span class="badge bg-light text-dark border small">{{ $item->purchase->invoice_number ?? '?' }}</span>
                                @endforeach
                            </td>
                            <td class="text-center">
                                @if($p->giro_is_paid)
                                    <span class="badge bg-success">Paid</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="text-center no-print">
                                @if(!$p->giro_is_paid)
                                <form method="POST" action="{{ route('supplier-payments.giro-paid', $p->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success py-0 px-2"
                                        onclick="return confirm('Mark GIRO payment {{ $p->payment_number }} as paid?')">
                                        <i class="ti ti-check me-1"></i> Mark Paid
                                    </button>
                                </form>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="ti ti-circle-check fs-3 d-block mb-2 text-success"></i>
                                No upcoming GIRO payments.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($payments->count() > 0)
                    <tfoot class="table-secondary">
                        <tr>
                            <td colspan="3" class="fw-bold">Total</td>
                            <td class="text-end fw-bold">{{ number_format($payments->sum('total_amount'), 2) }}</td>
                            <td colspan="3"></td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 no-print">
        <small class="text-muted">
            <i class="ti ti-info-circle me-1"></i>
            This report shows upcoming GIRO payments. Click <strong>Mark Paid</strong> after the amount has been deducted from your bank account.
        </small>
    </div>

</div>{{-- /#print-area --}}

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .table-dark th   { background-color: #343a40 !important; color: #fff !important; }
    .text-danger     { color: #dc3545 !important; }
    .text-success    { color: #198754 !important; }
    .table-secondary { background-color: #e9ecef !important; }
    .badge.bg-success { background-color: #198754 !important; color: #fff !important; }
    .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
    .badge.bg-danger  { background-color: #dc3545 !important; color: #fff !important; }
    .badge.bg-light   { background-color: #f8f9fa !important; color: #000 !important; border: 1px solid #ccc !important; }
    .opacity-50       { opacity: 0.5 !important; }
    #print-area .card { border: none !important; box-shadow: none !important; }
    #print-area .card-body { padding: 0 !important; }
}
</style>
@endpush
@endsection
