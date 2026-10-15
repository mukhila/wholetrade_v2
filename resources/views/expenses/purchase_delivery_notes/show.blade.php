@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Delivery Note Details</h4>
            <div class="page-title-right">
                <a href="{{ route('purchase-delivery-notes.index') }}" class="btn btn-secondary me-2">Back</a>
                <button onclick="window.print()" class="btn btn-info"><i class="ti ti-printer"></i> Print</button>
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
                        <div class="fw-bold text-primary mb-2">DELIVERY NOTE</div>
                        <h5 class="fw-bold mb-0">#{{ $deliveryNote->do_number }}</h5>
                        <div class="text-muted">Supplier Ref: {{ $deliveryNote->supplier_do_reference ?? '-' }}</div>
                        <div class="text-muted">Received: {{ $deliveryNote->delivery_date }}</div>
                        <div class="mt-2"><span class="badge bg-secondary fs-6">{{ strtoupper($deliveryNote->status) }}</span></div>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6>From (Supplier):</h6>
                        <address>
                            <strong>{{ $deliveryNote->supplier->name ?? 'N/A' }}</strong><br>
                            {!! nl2br(e($deliveryNote->billing_address)) !!}
                        </address>
                    </div>
                    <div class="col-6">
                        <h6>Received At (Warehouse):</h6>
                        <address>
                            {!! nl2br(e($deliveryNote->shipping_address)) !!}
                        </address>
                        <p><strong>Received By:</strong> {{ $deliveryNote->receiver->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Items -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product SKU</th>
                                <th>Description</th>
                                <th class="text-end">Box Qty</th>
                                <th class="text-end">Item Qty</th>
                                <th class="text-end">Total Loose Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deliveryNote->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product->sku ?? '-' }}</td>
                                <td>{{ $item->item_description }}</td>
                                <td class="text-end">{{ number_format($item->box_qty, 0) }}</td>
                                <td class="text-end">{{ number_format($item->item_qty, 0) }}</td>
                                <td class="text-end fw-bold">{{ number_format($item->total_qty_loose, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <p><strong>Remarks:</strong> {{ $deliveryNote->remarks }}</p>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="row mt-5 pt-5">
                    <div class="col-4 text-center">
                        <hr>
                        <p>Received By (Sign)</p>
                    </div>
                    <div class="col-4"></div>
                    <div class="col-4 text-center">
                        <hr>
                        <p>Authorized By (Sign)</p>
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
