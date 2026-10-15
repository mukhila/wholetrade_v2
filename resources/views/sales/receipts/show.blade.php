@extends('layouts.app')

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endpush

@section('content')
<div class="row no-print">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Receipt Details</h4>
            <div class="page-title-right">
                <a href="{{ route('sales-receipts.index') }}" class="btn btn-secondary me-2">
                    <i class="ti ti-arrow-left me-1"></i> Back
                </a>
                <a href="{{ route('sales-receipts.print', $receipt->id) }}" class="btn btn-info me-2" target="_blank">
                    <i class="ti ti-printer me-1"></i> Print Receipt
                </a>
                <a href="{{ route('sales-receipts.print', $receipt->id) }}" class="btn btn-outline-info" target="_blank">
                    <i class="ti ti-printer me-1"></i> Quick Print
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Company Header -->
                <div class="row align-items-center mb-4 border-bottom pb-4">
                    @include('partials.company-header')
                    <div class="col-4 text-end">
                        <div class="fw-bold text-primary mb-2 fs-5">PAYMENT RECEIPT</div>
                        <h4 class="fw-bold mb-0">#{{ $receipt->receipt_number }}</h4>
                        <div class="text-muted">Date: {{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d M Y') }}</div>
                        <div class="mt-2">
                            @if($receipt->status == 'posted')
                                <span class="badge bg-success fs-6">POSTED</span>
                            @elseif($receipt->status == 'draft')
                                <span class="badge bg-warning fs-6">DRAFT</span>
                            @else
                                <span class="badge bg-secondary fs-6">{{ strtoupper($receipt->status) }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Customer & Receipt Info -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="fw-bold text-muted mb-2">RECEIVED FROM:</h6>
                        <div class="border rounded p-3 bg-light">
                            <h5 class="fw-bold mb-1">{{ $receipt->customer->name ?? 'N/A' }}</h5>
                            @if($receipt->customer)
                                <div class="text-muted small">
                                    {{ $receipt->customer->code ?? '' }}<br>
                                    {{ $receipt->customer->address_line_1 ?? '' }}
                                    @if($receipt->customer->address_line_2), {{ $receipt->customer->address_line_2 }}@endif
                                    @if($receipt->customer->city), {{ $receipt->customer->city }}@endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="fw-bold text-muted mb-2">PAYMENT DETAILS:</h6>
                        <div class="border rounded p-3 bg-light">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Payment Mode:</td>
                                    <td class="fw-bold">{{ $receipt->payment_mode ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Reference:</td>
                                    <td class="fw-bold">{{ $receipt->reference ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Currency:</td>
                                    <td class="fw-bold">{{ $receipt->currency->symbol ?? $defaultCurrency->symbol ?? '$' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Amount Box -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="border rounded p-4 bg-primary bg-opacity-10 text-center">
                            <h6 class="text-muted mb-2">AMOUNT RECEIVED</h6>
                            <h1 class="display-4 fw-bold text-primary mb-0">
                                {{ $receipt->currency->symbol ?? $defaultCurrency->symbol ?? '$' }} {{ number_format($receipt->amount, 2) }}
                            </h1>
                            @php
                                $numberToWords = function(int $n) use (&$numberToWords): string {
                                    if ($n === 0) return 'zero';
                                    $ones = ['','one','two','three','four','five','six','seven','eight','nine',
                                             'ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen',
                                             'seventeen','eighteen','nineteen'];
                                    $tens = ['','','twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'];
                                    $r = '';
                                    if ($n >= 1000000) { $r .= $numberToWords((int)($n/1000000)).' million '; $n %= 1000000; }
                                    if ($n >= 1000)    { $r .= $numberToWords((int)($n/1000)).' thousand '; $n %= 1000; }
                                    if ($n >= 100)     { $r .= $ones[(int)($n/100)].' hundred '; $n %= 100; }
                                    if ($n >= 20)      { $r .= $tens[(int)($n/10)]; $n %= 10; if ($n) $r .= '-'.$ones[$n]; $r .= ' '; }
                                    elseif ($n > 0)    { $r .= $ones[$n].' '; }
                                    return trim($r);
                                };
                                $amountCents  = (int) round(($receipt->amount - floor($receipt->amount)) * 100);
                                $amountInWords = $numberToWords((int) floor($receipt->amount));
                            @endphp
                            <p class="text-muted mb-0 mt-2" style="text-transform: capitalize;">
                                {{ ucwords($amountInWords) }}{{ $amountCents > 0 ? ' and '.$amountCents.'/100' : '' }} Only
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Allocated Invoices -->
                @if($receipt->invoices && $receipt->invoices->count() > 0)
                <div class="mb-4">
                    <h6 class="fw-bold text-muted mb-3">ALLOCATED TO INVOICES:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Invoice Date</th>
                                    <th class="text-end">Invoice Total</th>
                                    <th class="text-end">Allocated Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receipt->invoices as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales-invoices.show', $invoice->id) }}" class="text-decoration-none">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $invoice->invoice_date }}</td>
                                    <td class="text-end">{{ number_format($invoice->net_total, 2) }}</td>
                                    <td class="text-end fw-bold text-success">{{ number_format($invoice->pivot->allocated_amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="3" class="text-end">Total Allocated:</th>
                                    <th class="text-end text-success">{{ number_format($receipt->invoices->sum('pivot.allocated_amount'), 2) }}</th>
                                </tr>
                                @if($receipt->unused_amount > 0)
                                <tr>
                                    <th colspan="3" class="text-end">Unused Amount:</th>
                                    <th class="text-end text-warning">{{ number_format($receipt->unused_amount, 2) }}</th>
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Customer Invoice History -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-muted mb-0">CUSTOMER INVOICE HISTORY:</h6>
                        <button onclick="printSelectedInvoices()" class="btn btn-sm btn-primary">
                            <i class="ti ti-printer me-1"></i> Print Selected
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="select-all-history">
                                    </th>
                                    <th>Invoice #</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($newInvoices->concat($oldInvoices)->sortByDesc(fn($i) => $i->invoice_date ?? $i->date) as $inv)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input history-checkbox" value="{{ $inv->id }}" data-type="{{ isset($inv->invoice_number) ? 'new' : 'old' }}">
                                    </td>
                                    <td>
                                        @if(isset($inv->invoice_number))
                                            <a href="{{ route('sales-invoices.show', $inv->id) }}" class="text-decoration-none">{{ $inv->invoice_number }}</a>
                                        @else
                                            {{ $inv->invoice_no }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($inv->invoice_number))
                                            <span class="badge bg-primary">System</span>
                                        @else
                                            <span class="badge bg-secondary">Migration</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ isset($inv->invoice_date) ? \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') : ($inv->date ? \Carbon\Carbon::parse($inv->date)->format('d M Y') : '-') }}
                                    </td>
                                    <td class="text-end">{{ number_format($inv->net_total, 2) }}</td>
                                    <td class="text-end text-success">{{ number_format(isset($inv->paid_amount) ? $inv->paid_amount : $inv->paid, 2) }}</td>
                                    <td class="text-end text-danger fw-bold">
                                        {{ number_format(isset($inv->balance_due) ? $inv->balance_due : $inv->balance, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $status = isset($inv->payment_status) ? $inv->payment_status : ($inv->status ?? 'OPEN');
                                            $bal = isset($inv->balance_due) ? $inv->balance_due : $inv->balance;
                                            if ($bal <= 0) $status = 'PAID';
                                        @endphp
                                        
                                        @if(strtoupper($status) == 'PAID' || strtoupper($status) == 'CLOSED')
                                            <span class="badge bg-success">PAID</span>
                                        @elseif(strtoupper($status) == 'PARTIAL' || strtoupper($status) == 'PARTIALLY PAID')
                                            <span class="badge bg-info">PARTIAL</span>
                                        @else
                                            <span class="badge bg-danger">UNPAID</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No invoice history found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($receipt->remarks)
                <div class="mb-4">
                    <h6 class="fw-bold text-muted mb-2">REMARKS:</h6>
                    <div class="border rounded p-3 bg-light">
                        {{ $receipt->remarks }}
                    </div>
                </div>
                @endif

                <!-- Signature Section -->
                <div class="row mt-5 pt-4 border-top">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="border-bottom border-dark mx-5 mb-2" style="height: 50px;"></div>
                            <p class="text-muted mb-0">Received By</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="border-bottom border-dark mx-5 mb-2" style="height: 50px;"></div>
                            <p class="text-muted mb-0">Authorized Signature</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0">Thank you for your payment. This is a computer-generated receipt.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Header Select All Checkbox
    document.getElementById('select-all-history').addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.history-checkbox').forEach(cb => {
            cb.checked = isChecked;
        });
    });

    function printSelectedInvoices() {
        // Collect selected invoices
        const selected = [];
        document.querySelectorAll('.history-checkbox:checked').forEach(cb => {
            selected.push({
                id: cb.value,
                type: cb.dataset.type // 'new' or 'old'
            });
        });

        if (selected.length === 0) {
            alert('Please select at least one invoice to print.');
            return;
        }

        // Create a hidden form to submit data
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('sales-receipts.print-selected') }}"; // New Route
        form.target = '_blank';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = "{{ csrf_token() }}";
        form.appendChild(csrfToken);

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'invoices';
        input.value = JSON.stringify(selected);
        form.appendChild(input);

        // Include customer ID for header info
        const custInput = document.createElement('input');
        custInput.type = 'hidden';
        custInput.name = 'customer_id';
        custInput.value = "{{ $receipt->customer_id }}";
        form.appendChild(custInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
</script>
@endpush
