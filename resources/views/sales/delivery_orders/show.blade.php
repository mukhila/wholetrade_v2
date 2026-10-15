@extends('layouts.app')

@section('content')
@php
    $returnUrl = request('return_url');
    if (! $returnUrl || ! str_starts_with($returnUrl, url('/'))) {
        $returnUrl = route('delivery-orders.index');
    }
@endphp
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Delivery Order</h4>
            <div class="page-title-right text-end">
                
                <a href="{{ $returnUrl }}" class="btn btn-secondary me-1">
                    <i data-lucide="arrow-left" class="icon-sm me-1"></i> Back
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i data-lucide="printer" class="icon-sm me-1"></i> Print
                </button>
            </div>
            
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <div class="card" id="print-area">
            <div class="card-body">
                <!-- Header -->
                <!-- Company Header -->
                <div class="row align-items-center mb-5 border-bottom pb-4">
                    @include('partials.company-header')
                    <div class="col-4 text-end">
                        <div class="fw-bold text-primary mb-2">DELIVERY ORDER</div>
                        <h5 class="fw-bold mb-0">#{{ $deliveryOrder->do_number }}</h5>
                        <div class="text-muted">Date: {{ \Carbon\Carbon::parse($deliveryOrder->delivery_date)->format('d M, Y') }}</div>
                        <div class="mt-2"><span class="badge bg-{{ $deliveryOrder->status === 'draft' ? 'secondary' : 'success' }} fs-6">{{ ucfirst($deliveryOrder->status) }}</span></div>
                    </div>
                </div>
                
                <hr>

                <!-- Addresses -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="fw-bold fs-14">To (Customer):</h6>
                        <address>
                            <strong>{{ $deliveryOrder->customer->name }}</strong><br>
                            {!! nl2br(e($deliveryOrder->shipping_address ?? $deliveryOrder->billing_address)) !!}<br>
                            <abbr title="Phone">P:</abbr> {{ $deliveryOrder->customer->phone }}
                        </address>
                    </div>
                    <div class="col-6 text-end">
                         <h6 class="fw-bold fs-14">Details:</h6>
                         @if($deliveryOrder->quotation_id)
                         <p class="mb-1">Sales Order Ref: {{ $deliveryOrder->quotation->quotation_number ?? $deliveryOrder->quotation_id }}</p>
                         @endif
                         <p class="mb-1">Salesperson: {{ $deliveryOrder->salesperson->name ?? '-' }}</p>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-end">Box Qty</th>
                                <th class="text-end">Item Qty</th>
                                <th class="text-end">Total Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deliveryOrder->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <b>{{ $item->product->name }}</b>
                                    @if($item->item_description && $item->item_description !== $item->product->name) <br><small class="text-muted">{{ $item->item_description }}</small> @endif
                                </td>
                                <td class="text-end">{{ number_format($item->box_qty) }}</td>
                                <td class="text-end">{{ number_format($item->item_qty) }}</td>
                                <td class="text-end fw-bold">{{ number_format($item->total_qty_loose) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                 @if($deliveryOrder->remarks)
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="fw-bold">Remarks:</h6>
                        <p>{{ $deliveryOrder->remarks }}</p>
                    </div>
                </div>
                @endif
                
                <div class="row mt-5">
                    <div class="col-md-6">
                        <p>Received By:</p>
                        <br><br>
                        <hr style="width: 50%">
                        <p class="text-muted">Signature & Stamp</p>
                    </div>
                    <div class="col-md-6 text-end">
                         <p>Authorized By:</p>
                        <br><br>
                        <hr style="width: 50%; float: right">
                    </div>
                </div>

                <div class="d-print-none mt-4 text-center">
                     <small class="text-muted">Generated by Whole Trade ERP</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


