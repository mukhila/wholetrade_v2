@extends('layouts.app')

@section('content')
@php
    $returnUrl = request('return_url');
    if (! $returnUrl || ! str_starts_with($returnUrl, url('/'))) {
        $returnUrl = route('admin.quotations.index');
    }
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0">Review Quotation: {{ $quotation->quotation_number }}</h4>
            <div class="page-title-right">
                <a href="{{ $returnUrl }}" class="btn btn-secondary me-2">Back</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Info -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Customer Details</h5>
                <p class="mb-1"><strong>Name:</strong> {{ $quotation->customer->name }}</p>
                <p class="mb-1"><strong>Code:</strong> {{ $quotation->customer->code }}</p>
                
                <h5 class="card-title mt-4 mb-3">Salesperson</h5>
                <p class="mb-1">{{ $quotation->salesperson->name }}</p>
                
                <h5 class="card-title mt-4 mb-3">Remarks</h5>
                <p class="text-muted">{{ $quotation->remarks ?: 'No remarks' }}</p>
            </div>
        </div>
        
        <!-- Actions Card -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Admin Actions</h5>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" id="btnApprove">
                        <i class="ti ti-check me-1"></i> Approve & Generate Invoice
                    </button>
                    <button class="btn btn-warning" id="btnRevise">
                        <i class="ti ti-edit me-1"></i> Submit Revision (Partial Stock)
                    </button>
                    <button class="btn btn-danger" id="btnReject">
                        <i class="ti ti-x me-1"></i> Reject Quotation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Product Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Item Qty</th>
                                <th>Box Qty</th>
                                <th>Stock</th>
                                <th>Prices</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @foreach($quotation->items as $item)
                            <tr data-id="{{ $item->id }}">
                                <td>
                                    <h6 class="m-0">{{ $item->product->name }}</h6>
                                    <small class="text-muted">{{ $item->product->sku }}</small>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm qty-input-item" style="width: 80px;" value="{{ $item->item_qty }}" min="0" data-original="{{ $item->item_qty }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm qty-input-box" style="width: 80px;" value="{{ $item->box_qty }}" min="0" data-original="{{ $item->box_qty }}">
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success">Available</span>
                                </td>
                                <td>
                                    <small>Item: ${{ number_format($item->unit_price, 2) }}</small><br>
                                    <small>Box: ${{ number_format($item->box_price, 2) }}</small>
                                </td>
                                <td class="fw-bold">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="5" class="text-end fw-bold">Grand Total:</td>
                                <td class="fw-bold fs-16">{{ number_format($quotation->grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts for Actions -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const id = "{{ $quotation->id }}";

        // Approve
        document.getElementById('btnApprove').addEventListener('click', function() {
            if(!confirm('Are you sure you want to approve this quotation? An invoice will be generated.')) return;
            
            axios.put(`/api/admin/quotations/${id}/approve`)
                .then(res => {
                    alert(res.data.message);
                    window.location.href = "{{ $returnUrl }}";
                })
                .catch(err => alert('Error: ' + err.response.data.error));
        });

        // Reject
        document.getElementById('btnReject').addEventListener('click', function() {
            const reason = prompt("Please enter a reason for rejection:");
            if(!reason) return;

            axios.put(`/api/admin/quotations/${id}/reject`, { reason })
                .then(res => {
                    alert(res.data.message);
                    window.location.href = "{{ $returnUrl }}";
                })
                .catch(err => alert('Error: ' + err.response.data.message));
        });

        // Revise
        document.getElementById('btnRevise').addEventListener('click', function() {
            const rows = document.querySelectorAll('#itemsBody tr');
            let items = [];
            let changed = false;

            rows.forEach(row => {
                const itemQtyInput = row.querySelector('.qty-input-item');
                const boxQtyInput = row.querySelector('.qty-input-box');
                
                const originalItem = parseInt(itemQtyInput.dataset.original) || 0;
                const currentItem = parseInt(itemQtyInput.value) || 0;
                
                const originalBox = parseInt(boxQtyInput.dataset.original) || 0;
                const currentBox = parseInt(boxQtyInput.value) || 0;
                
                if(currentItem !== originalItem || currentBox !== originalBox) changed = true;
                
                items.push({
                    id: row.dataset.id,
                    item_qty: currentItem,
                    box_qty: currentBox
                });
            });

            if(!changed) {
                alert("You haven't changed any quantities. Please modify quantities to submit a revision.");
                return;
            }
            
            const remarks = prompt("Add a note for the salesperson regarding this revision:");
            
            axios.put(`/api/admin/quotations/${id}/revise`, { items, remarks })
                .then(res => {
                    alert(res.data.message);
                    window.location.href = "{{ $returnUrl }}";
                })
                .catch(err => alert('Error: ' + err.response.data.error));
        });
    });
</script>
@endsection
