@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Purchase Orders</h4>
            <div class="page-title-right">
                @if($canCreate)
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Purchase Order
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
                                <th>PO Number</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Exp. Delivery</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseOrders as $po)
                            <tr id="po-{{ $po->id }}">
                                <td>{{ $po->po_number }}</td>
                                <td>{{ $po->po_date }}</td>
                                <td>
                                    <span class="fw-medium">{{ $po->supplier->name ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ $po->supplier->code ?? '' }}</small>
                                </td>
                                <td>{{ $po->expected_shipping_date ?? '-' }}</td>
                                <td class="text-end">{{ number_format($po->net_total, 2) }}</td>
                                <td>
                                    @include('partials.status_badge', ['status' => $po->status])
                                </td>
                                <td>
                                    <a href="{{ route('purchase-orders.show', ['purchase_order' => $po->id, 'return_url' => request()->fullUrl() . '#po-' . $po->id]) }}" class="btn btn-sm btn-soft-primary">
                                        <i class="ti ti-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No Purchase Orders found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $purchaseOrders->links() }}
                </div>
            </div>
        </div>
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
@push('scripts')
@vite(['resources/js/components/Expenses/CreditNoteForm.jsx'])
@endpush
