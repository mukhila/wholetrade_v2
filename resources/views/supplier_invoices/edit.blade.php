@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
@php
    $returnUrl = request('return_url');
    if (! $returnUrl || ! str_starts_with($returnUrl, url('/'))) {
        $returnUrl = route('supplier-invoices.index');
    }
    $showUrl = route('supplier-invoices.show', ['id' => $invoice->id, 'return_url' => $returnUrl]);
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Supplier Invoice — {{ $invoice->invoice_number }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ $returnUrl }}">Supplier Invoices</a></li>
                    <li class="breadcrumb-item"><a href="{{ $showUrl }}">{{ $invoice->invoice_number }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div id="supplier-invoice-form-root"
             data-suppliers="{{ json_encode($suppliers) }}"
             data-currencies="{{ json_encode($currencies) }}"
             data-products="{{ json_encode($products) }}"
             data-inv-number="{{ $invoice->invoice_number }}"
             data-edit-mode="true"
             data-invoice-id="{{ $invoice->id }}"
             data-initial-data="{{ json_encode([
                 'header' => [
                     'supplier_id'         => $invoice->supplier_id,
                     'currency_id'         => $invoice->currency_id,
                     'invoice_number'      => $invoice->invoice_number,
                     'invoice_date'        => $invoice->invoice_date->format('Y-m-d'),
                     'due_date'            => $invoice->due_date?->format('Y-m-d'),
                     'supplier_ref_number' => $invoice->supplier_ref_number,
                     'billing_address'     => $invoice->billing_address,
                     'remarks'             => $invoice->remarks,
                     'subtotal'            => $invoice->subtotal,
                     'discount_amount'     => $invoice->discount_amount,
                     'tax_amount'          => $invoice->tax_amount,
                     'net_total'           => $invoice->net_total,
                 ],
                 'items' => $invoice->items->map(fn($i) => [
                     'id'              => $i->id,
                     'product_id'      => $i->product_id,
                     'description'     => $i->item_description,
                     'box_qty'         => $i->box_qty,
                     'item_qty'        => $i->item_qty,
                     'total_qty'       => $i->total_qty,
                     'unit_price'      => $i->unit_price,
                     'discount_amount' => $i->discount_amount,
                     'tax_rate'        => $i->tax_rate,
                     'tax_amount'      => $i->tax_amount,
                     'total_price'     => $i->total_price,
                 ])->toArray()
             ]) }}">
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @vite(['resources/js/components/Supplier/SupplierInvoiceForm.jsx'])
@endpush
