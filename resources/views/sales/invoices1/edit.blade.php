@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Sales Invoice</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.index') }}">Invoices</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div id="sales-invoice-form-root"
             data-customers="{{ json_encode($customers) }}"
             data-currencies="{{ json_encode($currencies) }}"
             data-products="{{ json_encode($products) }}"
             data-salespeople="{{ json_encode($salespeople) }}"
             data-inv-number="{{ $invoice->invoice_number }}"
             data-edit-mode="true"
             data-invoice-id="{{ $invoice->id }}"
             data-initial-data="{{ json_encode([
                 'header' => [
                     'customer_id' => $invoice->customer_id,
                     'salesperson_id' => $invoice->salesperson_id,
                     'invoice_number' => $invoice->invoice_number,
                     'invoice_date' => $invoice->invoice_date,
                     'due_date' => $invoice->due_date,
                     'currency_id' => $invoice->currency_id,
                     'exchange_rate' => $invoice->exchange_rate,
                     'billing_address' => $invoice->billing_address,
                     'shipping_address' => $invoice->shipping_address,
                     'reference_number' => $invoice->reference_number,
                     'remarks' => $invoice->remarks,
                     'gross_total' => $invoice->gross_total,
                     'subtotal' => $invoice->subtotal,
                     'discount_amount' => $invoice->discount_amount,
                     'tax_amount' => $invoice->tax_amount,
                     'net_total' => $invoice->net_total,
                 ],
                 'items' => $invoice->items->map(fn($item) => [
                     'id' => $item->id,
                     'product_id' => $item->product_id,
                     'description' => $item->item_description,
                     'box_qty' => $item->box_qty,
                     'item_qty' => $item->item_qty,
                     'total_qty' => $item->total_qty,
                     'unit_price' => $item->unit_price,
                     'discount_amount' => $item->discount_amount,
                     'tax_rate' => $item->tax_rate,
                     'tax_amount' => $item->tax_amount,
                     'total_price' => $item->total_price,
                     'stock_qty_pcs' => $item->product?->stock_qty_pcs ?? 0,
                     'stock_qty_box' => $item->product?->stock_qty_box ?? 0,
                 ])->toArray()
             ]) }}">
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@vite(['resources/js/components/Sales/SalesInvoiceForm.jsx'])
@endpush
