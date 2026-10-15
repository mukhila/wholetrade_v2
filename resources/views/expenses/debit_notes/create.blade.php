@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">New Supplier Debit Note</h4>
        </div>
    </div>
</div>

<div id="debit-note-form-root" 
    data-suppliers='@json($suppliers)'
    data-products='@json($products)'
    data-currencies='@json($currencies)'
    data-taxes='@json($taxes)'
    data-transaction-number='{{ $transactionNumber }}'
></div>

@endsection

@push('scripts')
@vite(['resources/js/components/Expenses/DebitNoteForm.jsx'])
@endpush
