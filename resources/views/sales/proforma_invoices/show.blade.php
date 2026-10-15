@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Proforma Invoice Details</h4>
            <div class="page-title-right text-end">
                <a href="{{ route('proforma-invoices.index') }}" class="btn btn-secondary me-2">Back</a>
                <button onclick="window.print()" class="btn btn-info"><i class="ti ti-printer"></i> Print</button>
                <a href="{{ route('proforma-invoices.receipt', $pi->id) }}" target="_blank" onclick="window.open(this.href, 'receipt', 'width=400,height=600'); return false;" class="btn btn-primary"><i class="ti ti-bill"></i> Receipt</a>
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
                        <div class="fw-bold text-primary mb-2">PROFORMA INVOICE</div>
                        <h5 class="fw-bold mb-0">#{{ $pi->pi_number }}</h5>
                        <div class="text-muted">Date: {{ $pi->pi_date }}</div>
                        <div class="text-muted">Valid Till: {{ $pi->valid_till ?? 'N/A' }}</div>
                        <div class="mt-2"><span class="badge bg-secondary fs-6">{{ strtoupper($pi->status) }}</span></div>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="fw-bold">Bill To:</h6>
                        <address>
                            <strong>{{ $pi->customer->name ?? 'N/A' }}</strong><br>
                            {!! nl2br(e($pi->billing_address)) !!}
                        </address>
                        <p><strong>Salesperson:</strong> {{ $pi->salesperson->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-6">
                        <h6 class="fw-bold">Ship To:</h6>
                        <address>
                            {!! nl2br(e($pi->shipping_address)) !!}
                        </address>
                        @if($pi->reference_number)
                        <p><strong>Ref #:</strong> {{ $pi->reference_number }}</p>
                        @endif
                    </div>
                </div>

                <!-- Items -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        @php $showGst = isset($companySettings) && $companySettings->gst_include === 'Yes'; @endphp
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Disc</th>
                                @if($showGst)<th class="text-end">GST</th>@endif
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pi->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->product->sku ?? '' }}</strong> - {{ $item->item_description }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($item->box_qty, 0) }} Box / {{ number_format($item->item_qty, 0) }} Pcs<br>
                                    <small>Total: {{ number_format($item->total_qty, 0) }}</small>
                                </td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format($item->discount_amount, 2) }}</td>
                                @if($showGst)<td class="text-end">{{ number_format($item->tax_amount, 2) }}</td>@endif
                                <td class="text-end">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                             <tr>
                                <th colspan="{{ $showGst ? 6 : 5 }}" class="text-end">Subtotal</th>
                                <th class="text-end">{{ number_format($pi->subtotal, 2) }}</th>
                            </tr>
                            @if($pi->discount_amount > 0)
                            <tr>
                                <th colspan="{{ $showGst ? 6 : 5 }}" class="text-end">Discount</th>
                                <th class="text-end text-danger">-{{ number_format($pi->discount_amount, 2) }}</th>
                            </tr>
                            @endif
                            @if($showGst)
                            <tr>
                                <th colspan="6" class="text-end">GST</th>
                                <th class="text-end">{{ number_format($pi->tax_amount, 2) }}</th>
                            </tr>
                            @endif
                             <tr>
                                <th colspan="{{ $showGst ? 6 : 5 }}" class="text-end fs-5">Net Total ({{ $pi->currency->code ?? 'USD' }})</th>
                                <th class="text-end fs-5">{{ number_format($pi->net_total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                         <h6>Terms / Remarks:</h6>
                        <p>{{ $pi->remarks }}</p>
                    </div>
                </div>
                
                <div class="alert alert-warning mt-4 text-center">
                    This is a Proforma Invoice, not a Tax Invoice.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
