@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">
                Individual Supplier Ledger
                @if($supplier) — <span class="text-primary">{{ $supplier->name }}</span> @endif
            </h4>
            <div class="d-flex gap-2">
                @if($supplier)
                <a href="{{ route('supplier-reports.ledger.export', request()->only(['supplier_id','status'])) }}"
                   class="btn btn-outline-success no-print">
                    <i class="ti ti-file-download me-1"></i> Export CSV
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary no-print">
                    <i class="ti ti-printer me-1"></i> Print
                </button>
                @endif
                <a href="{{ route('supplier-reports.outstanding') }}" class="btn btn-outline-secondary no-print">
                    Outstanding Report
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Supplier Filter (screen only) --}}
<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Select Supplier <span class="text-danger">*</span></label>
                <select name="supplier_id" class="form-select form-select-sm" required>
                    <option value="">— Select Supplier —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="unpaid"  {{ request('status') === 'unpaid'  ? 'selected' : '' }}>Unpaid</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="paid"    {{ request('status') === 'paid'    ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">View Ledger</button>
            </div>
        </form>
    </div>
</div>

@if($supplier)
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
            SUPPLIER LEDGER
        </div>
        <div class="text-end" style="font-size:10px; margin-top:2px;">
            Printed: {{ now()->format('d M Y, h:i A') }}
            @if(request('status')) &nbsp;|&nbsp; Status: {{ ucfirst(request('status')) }} @endif
        </div>
        <hr style="border-top:1px solid #000; margin:4px 0 8px;">
    </div>

    {{-- Supplier Info --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-md-4">
                    <span class="text-muted small">Supplier</span>
                    <div class="fw-bold">{{ $supplier->name }}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Phone</span>
                    <div>{{ $supplier->phone ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Contact Person</span>
                    <div>{{ $supplier->contact_person ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ledger Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Particular (Invoice No.)</th>
                            <th>Date</th>
                            <th class="text-end">Invoice Amount</th>
                            <th class="text-end">Paid Amount</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledger as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $row->invoice_number }}</td>
                            <td>{{ $row->purchase_date->format('d M Y') }}</td>
                            <td class="text-end">{{ number_format($row->purchase_amount, 2) }}</td>
                            <td class="text-end text-success">{{ number_format($row->paid_amount, 2) }}</td>
                            <td class="text-end fw-bold {{ $row->balance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($row->balance, 2) }}
                            </td>
                            <td class="text-center">
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
                        <tr><td colspan="7" class="text-center py-4 text-muted">No records found.</td></tr>
                        @endforelse
                    </tbody>
                    @if($ledger->count() > 0 && $totals)
                    <tfoot class="table-secondary">
                        <tr>
                            <td colspan="3" class="fw-bold">Total</td>
                            <td class="text-end fw-bold">{{ number_format($totals['purchase_amount'], 2) }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($totals['paid_amount'], 2) }}</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($totals['balance'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>{{-- /#print-area --}}
@endif

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .table-dark th  { background-color: #343a40 !important; color: #fff !important; }
    .text-success   { color: #198754 !important; }
    .text-danger    { color: #dc3545 !important; }
    .table-secondary { background-color: #e9ecef !important; }
    .badge.bg-success { background-color: #198754 !important; color: #fff !important; }
    .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
    .badge.bg-danger  { background-color: #dc3545 !important; color: #fff !important; }
    #print-area .card { border: none !important; box-shadow: none !important; }
    #print-area .card-body { padding: 0 !important; }
    #print-area .card.mb-3 .card-body { padding: 8px !important; }
}
</style>
@endpush
@endsection
