@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">
                Create Sales Invoice
                @if($invoiceType === 'non_gst')
                    <span class="badge bg-secondary ms-2 fs-6 fw-normal">Without GST</span>
                @else
                    <span class="badge bg-primary ms-2 fs-6 fw-normal">GST</span>
                @endif
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.index') }}">Invoices</a></li>
                    <li class="breadcrumb-item active">Create</li>
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
             data-inv-number="{{ $invNumber }}"
             data-invoice-type="{{ $invoiceType }}">
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@vite(['resources/js/components/Sales/ProformaInvoiceForm.jsx']) 
{{-- Reusing Proforma Form Logic but with different endpoints/title usually. 
     For now, let's create a dedicated SalesInvoiceForm or reuse. 
     Given requirements are identical to PI form (Products, Service, Calc), we can Duplicate/Refactor. 
     I will create a NEW SalesInvoiceForm.jsx to be safe properly handling "payment" or "posting" logic. --}}
@vite(['resources/js/components/Sales/SalesInvoiceForm.jsx'])
@endpush
