@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box">
             <h4 class="page-title">Sales Credit Note</h4>
            <div class="page-title-right text-end">
                <a href="{{ route('credit-notes.index') }}" class="btn btn-secondary me-1">
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
                        <div class="fw-bold text-primary mb-2">CREDIT NOTE</div>
                        <h5 class="fw-bold mb-0">#{{ $creditNote->transaction_number }}</h5>
                        <div class="text-muted">Date: {{ \Carbon\Carbon::parse($creditNote->transaction_date)->format('d M, Y') }}</div>
                        <div class="mt-2"><span class="badge bg-{{ $creditNote->status === 'draft' ? 'secondary' : 'success' }} fs-6">{{ ucfirst($creditNote->status) }}</span></div>
                    </div>
                </div>
                
                <hr>

                <!-- Addresses -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="fw-bold fs-14">To (Customer):</h6>
                        <address>
                            <strong>{{ $creditNote->customer->name }}</strong><br>
                            {!! nl2br(e($creditNote->billing_address)) !!}<br>
                            <abbr title="Phone">P:</abbr> {{ $creditNote->customer->phone }}
                        </address>
                    </div>
                    <div class="col-6 text-end">
                         <h6 class="fw-bold fs-14">Details:</h6>
                         <p class="mb-1">Currency: {{ $creditNote->currency->code ?? '-' }}</p>
                         <p class="mb-1">Exchange Rate: {{ $creditNote->exchange_rate }}</p>
                         <p class="mb-1">Salesperson: {{ $creditNote->salesperson->name ?? '-' }}</p>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-centered mb-0">
                        @php $showGst = isset($companySettings) && $companySettings->gst_include === 'Yes'; @endphp
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-end">Box Qty</th>
                                <th class="text-end">Item Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Box Price</th>
                                @if($showGst)<th class="text-end">GST %</th>@endif
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($creditNote->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product->name }}</td>
                                <td class="text-end">{{ $item->box_qty }}</td>
                                <td class="text-end">{{ $item->item_qty }}</td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format($item->box_price, 2) }}</td>
                                @if($showGst)<td class="text-end">{{ number_format($item->tax_rate, 2) }}%</td>@endif
                                <td class="text-end fw-bold">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row justify-content-end">
                    <div class="col-sm-6 text-end">
                        <p class="mb-1"><b>Subtotal:</b> <span class="float-end">{{ number_format($creditNote->subtotal, 2) }}</span></p>
                        <p class="mb-1"><b>Discount ({{ $creditNote->discount_percentage }}%):</b> <span class="float-end">-{{ number_format($creditNote->discount_amount, 2) }}</span></p>
                        @if($showGst)
                        <p class="mb-1"><b>GST Total:</b> <span class="float-end">{{ number_format($creditNote->tax_amount, 2) }}</span></p>
                        @endif
                        <p class="mb-1"><b>Round Off:</b> <span class="float-end">{{ number_format($creditNote->round_off, 2) }}</span></p>
                        <hr>
                        <h4 class="fw-bold">Net Total: <span class="float-end">{{ number_format($creditNote->grand_total, 2) }}</span></h4>
                    </div>
                </div>
                
                @if($creditNote->remarks)
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="fw-bold">Remarks:</h6>
                        <p>{{ $creditNote->remarks }}</p>
                    </div>
                </div>
                @endif
                
                <div class="d-print-none mt-4 text-center">
                     <small class="text-muted">Generated by Whole Trade ERP</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
