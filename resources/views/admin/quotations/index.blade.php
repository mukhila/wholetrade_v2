@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Admin</a></li>
                        <li class="breadcrumb-item active">Quotation Review</li>
                    </ol>
                </div>
                <h4 class="page-title">Quotation Review</h4>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row">
        <div class="col-md-4">
            <div class="card bg-warning-subtle text-warning shadow-none">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-clock fs-24"></i>
                    </div>
                    <h5 class="mt-0 mb-1 text-warning">Pending Review</h5>
                    <h3 class="mb-0">{{ $pending }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle text-success shadow-none">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-check fs-24"></i>
                    </div>
                    <h5 class="mt-0 mb-1 text-success">Approved / Invoiced</h5>
                    <h3 class="mb-0">{{ $approved }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger-subtle text-danger shadow-none">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ti ti-ban fs-24"></i>
                    </div>
                    <h5 class="mt-0 mb-1 text-danger">Rejected</h5>
                    <h3 class="mb-0">{{ $rejected }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Pending Approvals</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Quotation #</th>
                                    <th>Salesperson</th>
                                    <th>Customer</th>
                                    <th>Grand Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quotations as $q)
                                    <tr id="q-{{ $q->id }}">
                                        <td><a href="{{ route('admin.quotations.show', [$q->id, 'return_url' => request()->fullUrl() . '#q-' . $q->id]) }}" class="fw-bold">{{ $q->quotation_number }}</a></td>
                                        <td>{{ $q->salesperson->name ?? 'Unknown' }}</td>
                                        <td>{{ $q->customer->name ?? 'Unknown' }}</td>
                                        <td>{{ number_format($q->grand_total, 2) }}</td>
                                        <td><span class="badge bg-warning text-dark">{{ str_replace('_', ' ', $q->status) }}</span></td>
                                        <td>{{ $q->created_at->format('M d, H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.quotations.show', [$q->id, 'return_url' => request()->fullUrl() . '#q-' . $q->id]) }}" class="btn btn-sm btn-light">Review</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-4">No pending quotations.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $quotations->links() }}
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
