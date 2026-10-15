@extends('layouts.app')

@push('styles')
<style>
    .balance-positive { color: #dc3545; font-weight: bold; }
    .balance-zero { color: #198754; font-weight: bold; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Old Sales Invoices</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <form method="GET" action="{{ route('old-sales-invoices.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by Invoice # or Customer..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="balance_filter" class="form-select">
                            <option value="">All Invoices</option>
                            <option value="with_balance" {{ request('balance_filter') == 'with_balance' ? 'selected' : '' }}>With Outstanding Balance</option>
                            <option value="paid" {{ request('balance_filter') == 'paid' ? 'selected' : '' }}>Fully Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('old-sales-invoices.index') }}" class="btn btn-secondary w-100">
                            <i class="ti ti-refresh me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="text-end">Net Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th class="text-center" style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $inv)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ $inv->invoice_no }}</span>
                                </td>
                                <td>{{ $inv->date ? $inv->date->format('d M Y') : '-' }}</td>
                                <td>{{ $inv->customer_name ?? ($inv->customer->name ?? 'N/A') }}</td>
                                <td class="text-end">{{ number_format($inv->net_total, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($inv->paid, 2) }}</td>
                                <td class="text-end {{ $inv->balance > 0 ? 'balance-positive' : 'balance-zero' }}">
                                    {{ number_format($inv->balance, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($inv->balance > 0)
                                    <a href="{{ route('old-sales-invoices.receipt', $inv->id) }}" class="btn btn-sm btn-soft-success" title="Make Receipt">
                                        <i class="ti ti-receipt me-1"></i> Make Receipt
                                    </a>
                                    @else
                                    <span class="badge bg-success">Paid</span>
                                    @endif
                                    <a href="{{ route('old-sales-invoices.print', $inv->id) }}" class="btn btn-sm btn-soft-info" title="Print" target="_blank">
                                        <i class="ti ti-printer"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No old invoices found.</td>
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
    </div>
</div>
@endsection
