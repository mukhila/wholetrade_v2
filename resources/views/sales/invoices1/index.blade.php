@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Sales Invoices</h4>
            <div class="page-title-right">
                <a href="{{ route('sales-invoices.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Invoice
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Balance</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $inv)
                            <tr>
                                <td>{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->invoice_date }}</td>
                                <td>
                                    <span class="fw-medium">{{ $inv->customer->name ?? 'N/A' }}</span>
                                </td>
                                <td class="text-end">{{ number_format($inv->net_total, 2) }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($inv->balance_due, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $inv->payment_status == 'paid' ? 'success' : ($inv->payment_status == 'partial' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($inv->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    @include('partials.status_badge', ['status' => $inv->status])
                                </td>
                                <td>
                                    <a href="{{ route('sales-invoices.show', $inv->id) }}" class="btn btn-sm btn-soft-primary" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    @if($inv->status === 'draft')
                                    <a href="{{ route('sales-invoices.edit', $inv->id) }}" class="btn btn-sm btn-soft-warning" title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    @endif
                                    @if(in_array($inv->status, ['confirmed', 'posted']) && $inv->balance_due > 0)
                                    <a href="{{ route('sales-invoices.pay', $inv->id) }}" class="btn btn-sm btn-soft-success" title="Record Payment">
                                        <i class="ti ti-cash"></i> Pay
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No Invoices found.</td>
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
