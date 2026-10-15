@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Supplier Payments</h4>
            @if($canCreate)<a href="{{ route('supplier-payments.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Add Payment
            </a>@endif
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
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
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button>
            </div>
            @if(request('supplier_id'))
            <div class="col-md-2">
                <a href="{{ route('supplier-payments.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Payment No.</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th>Reference</th>
                        <th class="text-end">Amount</th>
                        <th>Invoices</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                    <tr>
                        <td>{{ $payments->firstItem() + $loop->index }}</td>
                        <td class="fw-medium">{{ $p->payment_number }}</td>
                        <td>{{ $p->supplier->name ?? '—' }}</td>
                        <td>{{ $p->payment_date->format('d M Y') }}</td>
                        <td>
                            @if($p->payment_mode === 'GIRO')
                                <span class="badge bg-info">GIRO</span>
                                @if($p->giro_is_paid)
                                    <span class="badge bg-success ms-1">Paid</span>
                                @else
                                    <span class="badge bg-warning text-dark ms-1">Pending</span>
                                @endif
                            @elseif($p->payment_mode === 'Online Transfer')
                                <span class="badge bg-primary">Online</span>
                            @else
                                <span class="badge bg-secondary">Cash</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $p->reference_number ?? '—' }}</td>
                        <td class="text-end fw-bold text-success">{{ number_format($p->total_amount, 2) }}</td>
                        <td>
                            @foreach($p->items as $item)
                                <span class="badge bg-light text-dark border">{{ $item->purchase->invoice_number ?? '?' }}</span>
                            @endforeach
                        </td>
                        <td class="text-center">
                            <a href="{{ route('supplier-payments.show', $p->id) }}"
                                class="btn btn-sm btn-outline-primary px-2 py-0" title="View">
                                <i class="ti ti-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $payments->links() }}</div>
@endsection
