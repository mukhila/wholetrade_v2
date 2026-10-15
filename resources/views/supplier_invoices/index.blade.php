@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Supplier Invoices</h4>
            <div class="page-title-right">
                @if($canCreate)
                <a href="{{ route('supplier-invoices.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Supplier Invoice
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Status Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-primary">{{ $counts['all'] }}</div>
                <div class="text-muted small">All</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-warning">{{ $counts['draft'] }}</div>
                <div class="text-muted small">Draft</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-success">{{ $counts['confirmed'] }}</div>
                <div class="text-muted small">Confirmed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-danger">{{ $counts['unpaid'] }}</div>
                <div class="text-muted small">Unpaid</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-info">{{ $counts['partial'] }}</div>
                <div class="text-muted small">Partial</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-secondary">{{ $counts['paid'] }}</div>
                <div class="text-muted small">Paid</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if(session('success'))
            <div class="alert alert-success m-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger m-3">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Supplier Ref</th>
                        <th class="text-end">Net Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr id="invoice-{{ $inv->id }}">
                        <td><a href="{{ route('supplier-invoices.show', ['id' => $inv->id, 'return_url' => request()->fullUrl() . '#invoice-' . $inv->id]) }}" class="fw-semibold text-primary">{{ $inv->invoice_number }}</a></td>
                        <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</td>
                        <td>
                            <span class="fw-medium">{{ $inv->supplier->name ?? 'N/A' }}</span><br>
                            <small class="text-muted">{{ $inv->supplier->code ?? '' }}</small>
                        </td>
                        <td>{{ $inv->supplier_ref_number ?? '-' }}</td>
                        <td class="text-end">{{ $inv->currency->symbol ?? '$' }} {{ number_format($inv->net_total, 2) }}</td>
                        <td class="text-end text-success">{{ number_format($inv->paid_amount, 2) }}</td>
                        <td class="text-end {{ $inv->balance_due > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                            {{ number_format($inv->balance_due, 2) }}
                        </td>
                        <td>
                            @if($inv->status === 'confirmed')
                                <span class="badge bg-success">Confirmed</span>
                            @elseif($inv->status === 'cancelled')
                                <span class="badge bg-secondary">Cancelled</span>
                            @else
                                <span class="badge bg-warning text-dark">Draft</span>
                            @endif
                        </td>
                        <td>
                            @if($inv->payment_status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($inv->payment_status === 'partial')
                                <span class="badge bg-info">Partial</span>
                            @else
                                <span class="badge bg-danger">Unpaid</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('supplier-invoices.show', ['id' => $inv->id, 'return_url' => request()->fullUrl() . '#invoice-' . $inv->id]) }}" class="btn btn-sm btn-soft-primary" title="View">
                                    <i class="ti ti-eye"></i>
                                </a>
                                @if($inv->status === 'draft' && $canEdit)
                                <a href="{{ route('supplier-invoices.edit', ['id' => $inv->id, 'return_url' => request()->fullUrl() . '#invoice-' . $inv->id]) }}" class="btn btn-sm btn-soft-warning" title="Edit">
                                    <i class="ti ti-edit"></i>
                                </a>
                                @endif
                                @if($inv->canPay())
                                <a href="{{ route('supplier-invoices.pay', ['id' => $inv->id, 'return_url' => request()->fullUrl() . '#invoice-' . $inv->id]) }}" class="btn btn-sm btn-soft-success" title="Pay">
                                    <i class="ti ti-cash"></i>
                                </a>
                                @endif
                                <a href="{{ route('supplier-invoices.print', $inv->id) }}" target="_blank" class="btn btn-sm btn-soft-info" title="Print">
                                    <i class="ti ti-printer"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="ti ti-file-invoice fs-1 d-block mb-2"></i>
                            No supplier invoices found.@if($canCreate) <a href="{{ route('supplier-invoices.create') }}">Create one</a>@endif.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $invoices->links() }}</div>
    </div>
</div>

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
