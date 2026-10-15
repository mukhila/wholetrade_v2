@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="row mt-2 mb-2">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">New Sales Credit Note</h4>
        </div>
    </div>
</div>

<div id="sales-credit-note-form-root" 
    data-customers='@json($customers)'
    data-products='@json($products)'
    data-currencies='@json($currencies)'
    data-taxes='@json($taxes)'
    data-salespeople='@json($salespeople)'
    data-transaction-number='{{ $transactionNumber }}'
></div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@vite(['resources/js/components/Sales/SalesCreditNoteForm.jsx'])
@endpush
