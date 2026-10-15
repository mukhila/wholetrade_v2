@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Proforma Invoices</h4>
            <div class="page-title-right">
                @if($canCreate)
                <a href="{{ route('proforma-invoices.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Proforma
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
                                <th>PI Number</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Ref #</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proformaInvoices as $pi)
                            <tr>
                                <td>{{ $pi->pi_number }}</td>
                                <td>{{ $pi->pi_date }}</td>
                                <td>
                                    <span class="fw-medium">{{ $pi->customer->name ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ $pi->customer->code ?? '' }}</small>
                                </td>
                                <td>{{ $pi->reference_number ?? '-' }}</td>
                                <td class="text-end">{{ number_format($pi->net_total, 2) }}</td>
                                <td>
                                    @include('partials.status_badge', ['status' => $pi->status])
                                </td>
                                <td>
                                    <a href="{{ route('proforma-invoices.show', $pi->id) }}" class="btn btn-sm btn-soft-primary">
                                        <i class="ti ti-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No Proforma Invoices found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $proformaInvoices->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
