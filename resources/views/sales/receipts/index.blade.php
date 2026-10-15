@extends('layouts.app')

@push('styles')
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Sales Receipts (Payments)</h4>
            <div class="page-title-right">
                @if($canCreate)
                <a href="{{ route('sales-invoices.create') }}" class="btn btn-secondary me-2">
                    <i class="ti ti-file-invoice me-1"></i> Create Invoice
                </a>
                <a href="{{ route('sales-receipts.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Receipt
                </a>
                @endif
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
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Mode</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Unused</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receipts as $rcp)
                            <tr>
                                <td>
                                    <a href="{{ route('sales-receipts.show', $rcp->id) }}" class="fw-bold text-decoration-none">
                                        {{ $rcp->receipt_number }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($rcp->receipt_date)->format('d M Y') }}</td>
                                <td>{{ $rcp->customer->name ?? 'N/A' }}</td>
                                <td>{{ $rcp->payment_mode ?? '-' }}</td>
                                <td class="text-end fw-bold">{{ number_format($rcp->amount, 2) }}</td>
                                <td class="text-end text-muted">{{ number_format($rcp->unused_amount, 2) }}</td>
                                <td>
                                    @include('partials.status_badge', ['status' => $rcp->status])
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('sales-receipts.show', $rcp->id) }}" class="btn btn-sm btn-soft-primary" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="{{ route('sales-receipts.print', $rcp->id) }}" class="btn btn-sm btn-soft-info" title="Print" target="_blank">
                                        <i class="ti ti-printer"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No Receipts found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 <div class="mt-3">
                    {{ $receipts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
