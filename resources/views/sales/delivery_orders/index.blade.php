@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Delivery Orders</h4>
            <div class="page-title-right">
                @if($canCreate)
                <a href="{{ route('delivery-orders.create') }}" class="btn btn-primary">
                    <i data-lucide="plus" class="icon-sm me-1"></i> New Delivery Order
                </a>
                @endif
            </div>            
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DO Number</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deliveryOrders as $do)
                            <tr id="do-{{ $do->id }}">
                                <td>{{ $do->do_number }}</td>
                                <td>{{ $do->delivery_date }}</td>
                                <td>{{ $do->customer->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $do->status === 'draft' ? 'secondary' : 'success' }}">
                                        {{ ucfirst($do->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('delivery-orders.show', ['delivery_order' => $do->id, 'return_url' => request()->fullUrl() . '#do-' . $do->id]) }}" class="text-info me-2"><i data-lucide="eye" class="icon-sm"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No delivery orders found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $deliveryOrders->links() }}
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
