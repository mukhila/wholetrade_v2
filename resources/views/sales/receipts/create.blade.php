@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">New Receipt</h4>
            <div class="page-title-right">
                <a href="{{ route('sales-receipts.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div id="sales-receipt-form-root"
             data-customers="{{ json_encode($customers) }}"
             data-currencies="{{ json_encode($currencies) }}"
             data-banks="{{ json_encode($banks) }}"
             data-bank-accounts="{{ json_encode($bankAccounts) }}"
             data-paymodes="{{ json_encode($paymodes) }}"
             data-rcp-number="{{ $rcpNumber }}">
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@vite(['resources/js/components/Sales/SalesReceiptForm.jsx'])
@endpush
