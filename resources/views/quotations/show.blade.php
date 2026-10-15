@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Quotation Details: {{ $quotation->quotation_number }}</h4>
        </div>
    </div>
</div>

<!-- Rejection Alert -->
@if($quotation->status === 'REJECTED')
<div class="alert alert-danger" role="alert">
    <strong><i class="ti ti-alert-triangle me-1"></i> Quote Rejected</strong>
    <p class="mb-0 mt-1">Reason: {{ $quotation->admin_notes }}</p>
</div>
@endif

<!-- Revision Alert -->
@if($quotation->status === 'WAITING_FOR_SALES_APPROVAL')
<div class="alert alert-warning" role="alert">
    <strong><i class="ti ti-alert-circle me-1"></i> Action Required: Revision Review</strong>
    <p class="mb-1 mt-1">Admin has revised this quotation (likely due to stock availability). Please reviews the changes below:</p>
    <p class="mb-0"><strong>Admin Note:</strong> {{ $quotation->admin_notes }}</p>
    
    <div class="mt-3">
        <button class="btn btn-success me-2" id="btnAcceptRevision"><i class="ti ti-check me-1"></i> Accept Revision</button>
        <button class="btn btn-danger" id="btnRejectRevision"><i class="ti ti-x me-1"></i> Reject & Close</button>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">Product Items</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotation->items as $item)
                            <tr>
                                <td>
                                    <h5 class="font-size-14 text-truncate mb-1"><a href="#" class="text-dark">{{ $item->product->name }}</a></h5>
                                    <p class="text-muted mb-0">{{ $item->product->sku }}</p>
                                </td>
                                <td>{{ $item->quantity }} {{ $item->unit_type }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="3" class="text-end fw-bold">Grand Total</td>
                                <td class="text-end fw-bold fs-16">{{ number_format($quotation->grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">Customer Information</h4>
                <p class="mb-1"><strong>Name:</strong> {{ $quotation->customer->name }}</p>
                <p class="mb-1"><strong>Code:</strong> {{ $quotation->customer->code }}</p>
                
                <hr>
                
                <h4 class="header-title mb-3">Logs & Info</h4>
                <p class="mb-1"><strong>Current Status:</strong> <span class="badge bg-primary">{{ $quotation->status }}</span></p>
                <p class="mb-1"><strong>Your Remarks:</strong> {{ $quotation->remarks ?: '-' }}</p>
                <p class="mb-1"><strong>Date:</strong> {{ $quotation->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const id = "{{ $quotation->id }}";
        const btnAccept = document.getElementById('btnAcceptRevision');
        const btnReject = document.getElementById('btnRejectRevision');

        if(btnAccept) {
            btnAccept.addEventListener('click', function() {
                if(!confirm('Accept changes and generate invoice?')) return;
                axios.put(`/api/quotations/${id}/accept-revision`)
                    .then(res => {
                        alert(res.data.message);
                        window.location.reload();
                    })
                    .catch(err => alert('Error: ' + err.response.data.error));
            });
        }

        if(btnReject) {
            btnReject.addEventListener('click', function() {
                const reason = prompt("Enter rejection reason:");
                if(!reason) return;
                
                axios.put(`/api/quotations/${id}/reject-revision`, { reason })
                    .then(res => {
                        alert(res.data.message);
                        window.location.href = "{{ route('quotations.index') }}";
                    })
                    .catch(err => alert('Error: ' + err.response.data.error));
            });
        }
    });
</script>
@endsection
