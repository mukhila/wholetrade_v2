@extends('layouts.app')

@section('content')
@php
    $returnUrl = request('return_url');
    if (! $returnUrl || ! str_starts_with($returnUrl, url('/'))) {
        $returnUrl = route('purchase-orders.index');
    }
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Purchase Order Details</h4>
            <div class="page-title-right">
                <a href="{{ $returnUrl }}" class="btn btn-secondary me-2">Back</a>
                <button onclick="window.print()" class="btn btn-info"><i class="ti ti-printer"></i> Print PO</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Header -->
                <!-- Company Header -->
                <div class="row align-items-center mb-5 border-bottom pb-4">
                    @include('partials.company-header')
                    <div class="col-4 text-end">
                        <div class="fw-bold text-primary mb-2">PURCHASE ORDER</div>
                        <h5 class="fw-bold mb-0">#{{ $purchaseOrder->po_number }}</h5>
                        <div class="text-muted">Date: {{ $purchaseOrder->po_date }}</div>
                        <div class="mt-2"><span class="badge bg-secondary fs-6">{{ strtoupper($purchaseOrder->status) }}</span></div>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="fw-bold">Vendor:</h6>
                        <address>
                            <strong>{{ $purchaseOrder->supplier->name ?? 'N/A' }}</strong><br>
                            {{ $purchaseOrder->supplier->address_line_1 ?? '' }}<br>
                            {{ $purchaseOrder->supplier->city ?? '' }}<br>
                            Phone: {{ $purchaseOrder->supplier->phone ?? '' }}
                        </address>
                        @if($purchaseOrder->supplier_ref_no)
                        <p><strong>Vendor Ref:</strong> {{ $purchaseOrder->supplier_ref_no }}</p>
                        @endif
                    </div>
                    <div class="col-6">
                        <h6 class="fw-bold">Ship To:</h6>
                        <address>
                            {!! nl2br(e($purchaseOrder->shipping_address ?? 'Our Warehouse')) !!}
                        </address>
                        <p><strong>Expected Delivery:</strong> {{ $purchaseOrder->expected_shipping_date ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Items -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Item / Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrder->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if($item->product_id)
                                        <strong>{{ $item->product->sku ?? '' }}</strong> - {{ $item->product->name ?? '' }}
                                    @else
                                        <strong>{{ $item->gl_account ?? 'Service' }}</strong><br>
                                        {{ $item->description }}
                                    @endif
                                    @if($item->narration)
                                        <div class="text-muted small">{{ $item->narration }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($item->product_id)
                                        {{ number_format($item->box_qty, 0) }} Box / {{ number_format($item->item_qty, 0) }} Pcs<br>
                                        <small>Total: {{ number_format($item->total_qty, 0) }}</small>
                                    @else
                                        {{ number_format($item->total_qty, 2) }}
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                             <tr>
                                <th colspan="4" class="text-end">Subtotal</th>
                                <th class="text-end">{{ number_format($purchaseOrder->subtotal, 2) }}</th>
                            </tr>
                            @if($purchaseOrder->discount_amount > 0)
                            <tr>
                                <th colspan="4" class="text-end">Discount</th>
                                <th class="text-end text-danger">-{{ number_format($purchaseOrder->discount_amount, 2) }}</th>
                            </tr>
                            @endif
                            <tr>
                                <th colspan="4" class="text-end">Tax</th>
                                <th class="text-end">{{ number_format($purchaseOrder->tax_amount, 2) }}</th>
                            </tr>
                             <tr>
                                <th colspan="4" class="text-end fs-5">Total ({{ $purchaseOrder->currency->code ?? 'USD' }})</th>
                                <th class="text-end fs-5">{{ number_format($purchaseOrder->net_total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                         <h6>Remarks:</h6>
                        <p>{{ $purchaseOrder->remarks }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
@vite(['resources/js/components/Expenses/CreditNoteForm.jsx'])
@endpush
