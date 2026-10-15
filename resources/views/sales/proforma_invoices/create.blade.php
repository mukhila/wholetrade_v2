@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@vite(['resources/js/components/Sales/ProformaInvoiceForm.jsx'])
@endpush

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Create Proforma Invoice</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('proforma-invoices.index') }}">Proforma Invoices</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <div id="proforma-invoice-form-root"
             data-customers="{{ json_encode($customers) }}"
             data-currencies="{{ json_encode($currencies) }}"
             data-products="{{ json_encode($products) }}"
             data-salespeople="{{ json_encode($salespeople) }}"
             data-pi-number="{{ $piNumber }}">
        </div>
    </div>
</div>
@endsection


