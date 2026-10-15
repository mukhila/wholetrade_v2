@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
         <div class="page-title-box">
            <h4 class="page-title">Checkout</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Customer Information</h5>
                
                <form id="checkout-form">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Select Customer <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="customer_id" name="customer_id" required>
                            <option value="">Select Customer...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks / Notes</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter any specific notes for this order..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="expected_delivery_date" class="form-label">Expected Delivery Date (Optional)</label>
                        <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date">
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Order Items</h5>
                <div class="table-responsive">
                    <table class="table table-borderless table-centered mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Item Qty</th>
                                <th>Box Qty</th>
                                <th>Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart as $id => $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                         @if(isset($item['image']))
                                            <img src="{{ \Storage::disk('public_uploads')->url($item['image']) }}" class="avatar-xs rounded me-2" alt="">
                                        @endif
                                        <div>
                                            <h5 class="my-0 font-14">{{ $item['name'] }}</h5>
                                            <small class="text-muted">{{ $item['sku'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item['item_qty'] }}</td>
                                <td>{{ $item['box_qty'] }}</td>
                                <td>${{ number_format(($item['item_qty'] * $item['unit_price']) + ($item['box_qty'] * $item['box_price']), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-4">Final Summary</h5>
                <!-- Add discount logic here later -->
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Grand Total</span>
                    <span class="fw-bold fs-16">${{ number_format($grandTotal, 2) }}</span>
                </div>
                
                <hr>
                
                <button type="button" class="btn btn-primary w-100" id="btn-submit-quotation">
                    Submit Quotation
                </button>
                <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary w-100 mt-2">Back to Cart</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('btn-submit-quotation').addEventListener('click', function() {
    const btn = this;
    const form = document.getElementById('checkout-form');
    
    // Basic Client Validation
    if(!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const customerId = document.getElementById('customer_id').value;
    const remarks = document.getElementById('remarks').value;
    const deliveryDate = document.getElementById('expected_delivery_date').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    // Submit
    fetch("{{ route('quotations.store') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            customer_id: customerId,
            remarks: remarks,
            expected_delivery_date: deliveryDate
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.redirect) {
            window.location.href = data.redirect;
        } else if(data.error) {
            alert('Error: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = 'Submit Quotation';
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred.');
        btn.disabled = false;
        btn.innerHTML = 'Submit Quotation';
    });
});
</script>
@endpush
