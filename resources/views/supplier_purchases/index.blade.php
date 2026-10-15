@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Supplier Purchases</h4>
            <div class="page-title-right d-flex gap-2">
                {{-- Export respects active filters --}}
                <a href="{{ route('supplier-purchases.export', request()->only(['supplier_id','status'])) }}"
                   class="btn btn-outline-success">
                    <i class="ti ti-file-download me-1"></i> Export CSV
                </a>
                @if($canCreate)
                <a href="{{ route('supplier-purchases.import') }}" class="btn btn-outline-primary">
                    <i class="ti ti-file-upload me-1"></i> Import CSV
                </a>
                <a href="{{ route('supplier-payments.create') }}" class="btn btn-success">
                    <i class="ti ti-cash me-1"></i> Add Payment
                </a>
                <a href="{{ route('supplier-purchases.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add Purchase
                </a>
                @endif
            </div>
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
                <button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button>
            </div>
            @if(request()->hasAny(['supplier_id','status']))
            <div class="col-md-2">
                <a href="{{ route('supplier-purchases.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Supplier</th>
                        <th>Invoice No.</th>
                        <th>Purchase Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $p)
                    <tr>
                        <td>{{ $purchases->firstItem() + $loop->index }}</td>
                        <td>{{ $p->supplier->name ?? '—' }}</td>
                        <td class="fw-medium">{{ $p->invoice_number }}</td>
                        <td>{{ $p->purchase_date->format('d M Y') }}</td>
                        <td class="text-end">{{ number_format($p->purchase_amount, 2) }}</td>
                        <td class="text-end text-success">{{ number_format($p->paid_amount, 2) }}</td>
                        <td class="text-end fw-bold {{ $p->balance > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($p->balance, 2) }}
                        </td>
                        <td class="text-center">
                            @if($p->payment_status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($p->payment_status === 'partial')
                                <span class="badge bg-warning text-dark">Partial</span>
                            @else
                                <span class="badge bg-danger">Unpaid</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($canEdit)
                            <a href="{{ route('supplier-purchases.edit', $p->id) }}" class="btn btn-xs btn-outline-primary btn-sm px-2 py-0" title="Edit">
                                <i class="ti ti-edit"></i>
                            </a>
                            @endif
                            @if($p->paid_amount == 0)
                            @if($canDelete)
                                            <form method="POST" action="{{ route('supplier-purchases.destroy', $p->id) }}" class="d-inline"
                                onsubmit="return confirm('Delete this purchase?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger btn-sm px-2 py-0" title="Delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                                            @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No purchases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $purchases->links() }}</div>
@endsection
