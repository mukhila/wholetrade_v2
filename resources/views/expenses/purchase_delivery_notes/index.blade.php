@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Purchase Delivery Notes (Inbound)</h4>
            <div class="page-title-right text-end">
                @if($canCreate)
                <a href="{{ route('purchase-delivery-notes.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Inbound Note
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
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ref No</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Received By</th>
                                <th>Supplier Ref</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deliveryNotes as $dn)
                            <tr>
                                <td>{{ $dn->do_number }}</td>
                                <td>{{ $dn->delivery_date }}</td>
                                <td>
                                    <span class="fw-medium">{{ $dn->supplier->name ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ $dn->supplier->code ?? '' }}</small>
                                </td>
                                <td>{{ $dn->receiver->name ?? 'N/A' }}</td>
                                <td>{{ $dn->supplier_do_reference ?? '-' }}</td>
                                <td>
                                    @if($dn->status == 'submitted')
                                        <span class="badge bg-success">Received</span>
                                    @elseif($dn->status == 'unified')
                                        <span class="badge bg-info">Verified</span>
                                    @else
                                        <span class="badge bg-warning">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('purchase-delivery-notes.show', $dn->id) }}" class="btn btn-sm btn-soft-primary">
                                        <i class="ti ti-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No Purchase Delivery Notes found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="mt-3">
                    {{ $deliveryNotes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
@vite(['resources/js/components/Expenses/CreditNoteForm.jsx'])
@endpush
