@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
         <div class="page-title-box">
            <h4 class="page-title">Shopping Cart</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless table-centered mb-0 items-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Item Price</th>
                                <th>Box Price</th>
                                <th>Item Qty</th>
                                <th>Box Qty</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandTotal = 0; @endphp
                            @foreach((array) session('cart') as $id => $details)
                            @php 
                                $itemTotal = ($details['item_qty'] ?? 0) * ($details['unit_price'] ?? 0);
                                $boxTotal = ($details['box_qty'] ?? 0) * ($details['box_price'] ?? 0);
                                $lineTotal = $itemTotal + $boxTotal;
                                $grandTotal += $lineTotal;
                            @endphp
                            <tr data-id="{{ $id }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if(isset($details['image']))
                                            <img src="{{ \Storage::disk('public_uploads')->url($details['image']) }}" class="img-fluid me-3 rounded" style="width: 50px; height: 50px; object-fit: cover;" alt="Product">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center me-3 rounded" style="width: 50px; height: 50px;">
                                                <i data-lucide="image" class="text-secondary"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <h5 class="mb-0 text-truncate" style="max-width: 200px;">{{ $details['name'] }}</h5>
                                            <small class="text-muted">SKU: {{ $details['sku'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>${{ number_format($details['unit_price'], 2) }}</td>
                                <td>${{ number_format($details['box_price'], 2) }}</td>
                                <td width="140px">
                                    <div class="input-group input-group-sm quantity-group">
                                        <button class="btn btn-outline-secondary btn-minus" type="button"><i data-lucide="minus" class="icon-xs"></i></button>
                                        <input type="number" value="{{ $details['item_qty'] ?? 0 }}" class="form-control text-center update-cart-item no-arrow" min="0" data-price="{{ $details['unit_price'] }}">
                                        <button class="btn btn-outline-secondary btn-plus" type="button"><i data-lucide="plus" class="icon-xs"></i></button>
                                    </div>
                                </td>
                                <td width="140px">
                                    <div class="input-group input-group-sm quantity-group">
                                        <button class="btn btn-outline-secondary btn-minus" type="button"><i data-lucide="minus" class="icon-xs"></i></button>
                                        <input type="number" value="{{ $details['box_qty'] ?? 0 }}" class="form-control text-center update-cart-box no-arrow" min="0" data-price="{{ $details['box_price'] }}">
                                        <button class="btn btn-outline-secondary btn-plus" type="button"><i data-lucide="plus" class="icon-xs"></i></button>
                                    </div>
                                </td>
                                <td class="fw-bold row-total text-primary">${{ number_format($lineTotal, 2) }}</td>
                                <td>
                                    <button class="btn btn-sm btn-soft-danger remove-from-cart" data-bs-toggle="tooltip" title="Remove Item">
                                        <i data-lucide="trash-2" class="icon-sm"></i>
                                    </button>
                                </td>
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
                <h5 class="card-title fw-bold mb-4">Order Summary</h5>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span class="fw-bold" id="cart-subtotal">${{ number_format($grandTotal, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax (0%)</span>
                    <span>$0.00</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-16">Total</span>
                    <span class="fw-bold fs-16 text-primary" id="cart-total">${{ number_format($grandTotal, 2) }}</span>
                </div>

                <div class="d-grid gap-2">
                    <a href="{{ route('checkout.index') }}" class="btn btn-success py-2">
                        <i data-lucide="check-circle" class="icon-sm me-1"></i> Proceed to Checkout
                    </a>
                    <a href="{{ route('products.index') }}" class="btn btn-light">Continue Shopping</a>
                    <a href="{{ route('cart.clear') }}" class="btn btn-outline-danger btn-sm mt-3">Clear Cart</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Hide number arrows */
    .no-arrow::-webkit-outer-spin-button,
    .no-arrow::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .no-arrow {
        -moz-appearance: textfield;
    }
    .items-middle td {
        vertical-align: middle;
    }
    .icon-xs {
        width: 14px;
        height: 14px;
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Single Source of Truth: Server Sync
    function syncCart(id, payload, row) {
        fetch('{{ route('cart.update') }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            // Update Row Total
            const rowTotalEl = row.querySelector('.row-total');
            if(rowTotalEl) rowTotalEl.textContent = '$' + data.line_total;

            // Update Grand Totals
            const subtotalEl = document.getElementById('cart-subtotal');
            const totalEl = document.getElementById('cart-total');
            if(subtotalEl) subtotalEl.textContent = '$' + data.grand_total;
            if(totalEl) totalEl.textContent = '$' + data.grand_total;
        })
        .catch(error => {
            console.error('Error updating cart:', error);
        });
    }

    // Update Item Qty Function
    function updateItemQty(input) {
        const row = input.closest('tr');
        const id = row.getAttribute('data-id');
        const value = parseInt(input.value) || 0;
        
        syncCart(id, { id: id, item_qty: value }, row);
    }

    // Update Box Qty Function
    function updateBoxQty(input) {
        const row = input.closest('tr');
        const id = row.getAttribute('data-id');
        const value = parseInt(input.value) || 0;
        
        syncCart(id, { id: id, box_qty: value }, row);
    }

    // Handle Plus/Minus Click with Delegation
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-plus, .btn-minus');
        if (!btn) return;
        
        e.preventDefault();
        
        const inputGroup = btn.closest('.input-group');
        const input = inputGroup.querySelector('input');
        const isPlus = btn.classList.contains('btn-plus');
        let value = parseInt(input.value) || 0;

        if (isPlus) {
            value++;
        } else {
            if (value > 0) value--;
        }
        
        input.value = value;
        
        // Directly trigger update logic
        if (input.classList.contains('update-cart-item')) {
            updateItemQty(input);
        } else if (input.classList.contains('update-cart-box')) {
            updateBoxQty(input);
        }
    });

    // Update Item Qty on manual input change
    document.querySelectorAll('.update-cart-item').forEach(input => {
        input.addEventListener('change', function() {
            updateItemQty(this);
        });
    });

    // Update Box Qty on manual input change
    document.querySelectorAll('.update-cart-box').forEach(input => {
        input.addEventListener('change', function() {
            updateBoxQty(this);
        });
    });

    // Remove from Cart
    document.querySelectorAll('.remove-from-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            if(!confirm("Are you sure you want to remove this item?")) return;
            
            let id = this.closest('tr').getAttribute('data-id');

            fetch('{{ route('cart.remove') }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload(); 
                }
            })
            .catch(error => {
                console.error('Error removing item:', error);
            });
        });
    });
});
</script>
@endpush
