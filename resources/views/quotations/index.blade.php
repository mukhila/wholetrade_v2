@extends('layouts.app')

@section('content')

    <div class="row mt-2">
        <div class="col-12">
            <h4 class="page-title">My Quotations</h4>
            <div class="page-title-box mb-2">
                <div class="page-title-right text-end">
                    @if($canCreate)
                    <a href="{{ route('quotations.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> New Quotation
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
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Quotation #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Grand Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quotations as $quotation)
                                    <tr>
                                        <td>
                                            <a href="#" class="text-body fw-bold">{{ $quotation->quotation_number }}</a>
                                        </td>
                                        <td>
                                            {{ $quotation->customer->name ?? 'N/A' }} 
                                            @if($quotation->customer)<small class="text-muted d-block">{{ $quotation->customer->code }}</small>@endif
                                        </td>
                                        <td>{{ $quotation->created_at->format('M d, Y') }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($quotation->status) {
                                                    'PENDING_ADMIN_APPROVAL' => 'bg-warning-subtle text-warning',
                                                    'APPROVED_BY_ADMIN' => 'bg-success-subtle text-success',
                                                    'REJECTED' => 'bg-danger-subtle text-danger',
                                                    default => 'bg-secondary-subtle text-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }} px-2 py-1 fs-12">{{ str_replace('_', ' ', $quotation->status) }}</span>
                                        </td>
                                        <td>{{ number_format($quotation->grand_total, 2) }}</td>
                                        <td>
                                            <a href="javascript:void(0);" class="action-icon"> <i class="fas fa-eye"></i></a>
                                            <a href="javascript:void(0);" class="action-icon"> <i class="fas fa-pencil"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No quotations found.</td>
                                    </tr>
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

@endsection
