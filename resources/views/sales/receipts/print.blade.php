<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt #{{ $receipt->receipt_number }}</title>
    <style>
        @font-face {
            font-family: 'Noto Sans';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('/assets/fonts/NotoSans-Regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Noto Sans';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('/assets/fonts/NotoSans-Bold.woff2') format('woff2');
        }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page { size: A4; margin: 8mm 10mm; }
            body { margin: 0; font-family: 'Noto Sans', Arial, sans-serif; font-size: 10px; line-height: 1.4; }
            .no-print { display: none !important; }
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Noto Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: auto;
            background: white;
            padding: 8mm 10mm;
            position: relative;
        }

        /* ── NO-PRINT ACTIONS ── */
        .no-print {
            text-align: center;
            margin-bottom: 12px;
            padding: 10px;
            background: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 7px 18px;
            font-size: 11px;
            font-family: 'Noto Sans', Arial, sans-serif;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            border: none;
            margin: 0 4px;
            border-radius: 3px;
        }
        .btn-primary   { background: #0d6efd; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }

        /* ── RECEIPT TITLE ── */
        .doc-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
            border: 1px solid #000;
            padding: 4px 0;
            text-transform: uppercase;
        }

        /* ── COMPANY HEADER ── */
        .company-header {
            display: flex;
            align-items: center;
            border-left: 1.5px solid #000;
            border-right: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
        }
        .company-logo {
            width: 100px;
            flex-shrink: 0;
            padding: 5px;
        }
        .company-logo img {
            max-width: 80px;
            max-height: 100px;
            object-fit: contain;
        }
        .company-name-block {
            flex: 1;
            padding-left: 10px;
        }
        .company-name-text {
            font-size: 30px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            line-height: 1.1;
            font-family: 'Noto Sans', Arial, sans-serif;
            text-align: center;
            color: #09783e;
        }
        .company-address {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
        }

        /* ── INFO ROW ── */
        .info-row {
            display: flex;
            justify-content: space-between;
            border-left: 1.5px solid #000;
            border-right: 1.5px solid #000;
            padding: 6px 10px;
        }
        .received-from-block { width: 55%; }
        .received-from-label { font-weight: bold; font-size: 10px; margin-bottom: 2px; }
        .received-from-name  { font-size: 13px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .received-from-addr  { font-size: 10px; line-height: 1.5; }

        .receipt-meta { width: 40%; text-align: right; }
        .receipt-meta-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 3px;
            font-size: 10px;
        }
        .receipt-meta-label { font-weight: bold; margin-right: 6px; }
        .receipt-meta-value { min-width: 110px; text-align: left; }

        /* ── STATUS BADGE ── */
        .status-badge {
            display: inline-block;
            padding: 1px 7px;
            font-weight: bold;
            font-size: 10px;
            border: 1.5px solid;
            border-radius: 2px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .status-posted  { color: #166534; border-color: #166534; background: #dcfce7; }
        .status-draft   { color: #92400e; border-color: #92400e; background: #fef3c7; }
        .status-void    { color: #991b1b; border-color: #991b1b; background: #fee2e2; }

        /* ── AMOUNT HIGHLIGHT ROW ── */
        .amount-highlight {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #000;
            border-top: none;
            padding: 6px 10px;
        }
        .amount-words-block {
            font-size: 10px;
            font-style: italic;
        }
        .amount-total-block { text-align: right; }
        .amount-total-label { font-size: 13px; font-weight: bold; }
        .amount-total-value { font-size: 20px; font-weight: 900; }

        /* ── ALLOCATION TABLE ── */
        .alloc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .alloc-table th, .alloc-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10px;
        }
        .alloc-table thead tr th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
        }
        .alloc-table tfoot tr th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .text-end   { text-align: right; }
        .text-center { text-align: center; }
        .col-inv    { width: 110px; }
        .col-date   { width: 80px; text-align: center; }
        .col-amt    { width: 90px; text-align: right; }
        .col-alloc  { width: 90px; text-align: right; font-weight: bold; }

        /* ── FOOTER SECTION ── */
        .footer-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 0;
        }
        .footer-payment {
            width: 40%;
            font-size: 10px;
            line-height: 1.7;
        }
        .footer-qr {
            width: 28%;
            text-align: center;
        }
        .footer-qr .qr-company-name {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .footer-qr img {
            width: 90px;
            height: 90px;
            object-fit: contain;
        }
        .footer-qr .scan-label {
            font-size: 9px;
            font-weight: bold;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer-right {
            width: 28%;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
        }
        .footer-right-amount {
            font-size: 15px;
            font-weight: 900;
        }

        /* ── REMARKS ── */
        .remarks-row {
            border: 1px solid #000;
            border-top: none;
            padding: 5px 8px;
            font-size: 10px;
        }
        .remarks-label { font-weight: bold; margin-bottom: 2px; }

        /* ── SIGNATURES ── */
        .signatures-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border: 1px solid #000;
            border-top: none;
        }
        .sign-cell {
            flex: 1;
            text-align: center;
            padding: 6px 4px;
            border-right: 1px solid #000;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .sign-cell:last-child { border-right: none; }
        .sign-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
        .sign-image {
            max-height: 40px;
            max-width: 120px;
            object-fit: contain;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

@php
    $currencyCode   = $receipt->currency->code ?? $defaultCurrency->code ?? 'SGD';
    $currencySymbol = $receipt->currency->symbol ?? $defaultCurrency->symbol ?? '$';
    $amount         = $receipt->amount ?? 0;
    $unusedAmount   = $receipt->unused_amount ?? 0;

    // Amount in words
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
             'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
             'Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    $n = (int) floor($amount);
    $cents = (int) round(($amount - floor($amount)) * 100);
    $w = '';
    if ($n >= 1000) { $w .= $ones[intdiv($n,1000)].' Thousand '; $n %= 1000; }
    if ($n >= 100)  { $w .= $ones[intdiv($n,100)].' Hundred ';   $n %= 100;  }
    if ($n >= 20)   { $w .= $tens[intdiv($n,10)].' ';            $n %= 10;   }
    if ($n > 0)     { $w .= $ones[$n].' '; }
    $amountWords = trim($w) ?: 'Zero';
    $amountWords .= ' ' . $currencyCode;
    if ($cents > 0) { $amountWords .= ' and ' . $cents . ' Cents'; }
    $amountWords .= ' Only';

    $statusClass = match($receipt->status ?? '') {
        'posted' => 'status-posted',
        'void'   => 'status-void',
        default  => 'status-draft',
    };
@endphp

{{-- No-print actions --}}
<div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
    <a href="{{ route('sales-receipts.show', $receipt->id) }}" class="btn btn-secondary">Back to Details</a>
</div>

<div class="a4-container">

    {{-- ── TITLE ── --}}
    <div class="doc-title">Payment Receipt</div>

    {{-- ── COMPANY HEADER ── --}}
    <div class="company-header">
        <div class="company-logo">
            @if(isset($companySettings) && $companySettings->logo)
                <img src="{{ \Storage::disk('public_uploads')->url($companySettings->logo) }}" alt="Logo">
            @else
                <img src="{{ asset('assets/images/logo.png') }}" alt="Logo">
            @endif
        </div>
        <div class="company-name-block">
            <div class="company-name-text">{{ $companySettings->company_name ?? 'SG TRADE PTE LTD' }}</div>
            <div class="company-address">
                {{ $companySettings->address ?? '' }}
                @if(isset($companySettings) && $companySettings->phone_no)
                    &nbsp;&nbsp;PHONE No : {{ $companySettings->phone_no }}
                @endif
            </div>
        </div>
    </div>

    {{-- ── INFO ROW ── --}}
    <div class="info-row">
        <div class="received-from-block">
            <div class="received-from-label">RECEIVED FROM:</div>
            <div class="received-from-name">{{ $receipt->customer->name ?? 'N/A' }}</div>
            @if($receipt->customer)
            <div class="received-from-addr">
                {{ $receipt->customer->code ?? '' }}
                @if($receipt->customer->address_line_1)
                    <br>{{ $receipt->customer->address_line_1 }}
                    @if($receipt->customer->address_line_2), {{ $receipt->customer->address_line_2 }}@endif
                    @if($receipt->customer->city), {{ $receipt->customer->city }}@endif
                @endif
                @if($receipt->customer->phone ?? false)
                    <br>{{ $receipt->customer->phone }}
                @endif
            </div>
            @endif
        </div>
        <div class="receipt-meta">
            <div class="receipt-meta-row">
                <span class="receipt-meta-label">Receipt No :</span>
                <span class="receipt-meta-value">{{ $receipt->receipt_number }}</span>
            </div>
            <div class="receipt-meta-row">
                <span class="receipt-meta-label">Receipt Date :</span>
                <span class="receipt-meta-value">{{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') }}</span>
            </div>
            <div class="receipt-meta-row">
                <span class="receipt-meta-label">Payment Mode :</span>
                <span class="receipt-meta-value">{{ $receipt->payment_mode ?? 'N/A' }}</span>
            </div>
            @if($receipt->reference)
            <div class="receipt-meta-row">
                <span class="receipt-meta-label">Reference :</span>
                <span class="receipt-meta-value">{{ $receipt->reference }}</span>
            </div>
            @endif
            <div class="receipt-meta-row" style="margin-top:4px;">
                <span class="receipt-meta-label">Status :</span>
                <span class="receipt-meta-value">
                    <span class="status-badge {{ $statusClass }}">{{ strtoupper($receipt->status ?? 'draft') }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- ── ALLOCATION TABLE ── --}}
    @if($receipt->invoices && $receipt->invoices->count() > 0)
    <table class="alloc-table">
        <thead>
            <tr>
                <th class="col-inv">Invoice No</th>
                <th class="col-date">Invoice Date</th>
                <th class="col-amt">Invoice Amount</th>
                <th class="col-alloc">Allocated Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipt->invoices as $inv)
            <tr>
                <td class="col-inv">{{ $inv->invoice_number }}</td>
                <td class="col-date">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d/m/Y') }}</td>
                <td class="col-amt">{{ $currencySymbol }}{{ number_format($inv->net_total, 2) }}</td>
                <td class="col-alloc">{{ $currencySymbol }}{{ number_format($inv->pivot->allocated_amount, 2) }}</td>
            </tr>
            @endforeach

            {{-- Filler rows --}}
            @php $fillerCount = max(0, 8 - $receipt->invoices->count()); @endphp
            @for($f = 0; $f < $fillerCount; $f++)
            <tr style="height:20px;">
                <td></td><td></td><td></td><td></td>
            </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total Allocated :</th>
                <th class="col-alloc">{{ $currencySymbol }}{{ number_format($receipt->invoices->sum('pivot.allocated_amount'), 2) }}</th>
            </tr>
        </tfoot>
    </table>
    @else
    {{-- No invoice allocation — empty placeholder table --}}
    <table class="alloc-table">
        <thead>
            <tr>
                <th class="col-inv">Invoice No</th>
                <th class="col-date">Invoice Date</th>
                <th class="col-amt">Invoice Amount</th>
                <th class="col-alloc">Allocated Amount</th>
            </tr>
        </thead>
        <tbody>
            @for($f = 0; $f < 8; $f++)
            <tr style="height:20px;"><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>
    @endif

    {{-- ── AMOUNT IN WORDS + TOTAL ── --}}
    <div class="amount-highlight">
        <div class="amount-words-block">({{ $amountWords }})</div>
        <div class="amount-total-block">
            <div class="amount-total-label">AMOUNT RECEIVED</div>
            <div class="amount-total-value">{{ $currencySymbol }}{{ number_format($amount, 2) }}</div>
        </div>
    </div>

    {{-- ── FOOTER: PAYMENT INFO + QR + UNUSED ── --}}
    @if($receipt->remarks)
    <div class="remarks-row">
        <div class="remarks-label">Remarks:</div>
        <div>{{ $receipt->remarks }}</div>
    </div>
    @endif

    <div class="footer-section">
        {{-- Left: payment details --}}
        <div class="footer-payment">
            @if(isset($companySettings) && $companySettings->uen_no)
            <div><strong>Paynow UEN NUMBER : {{ $companySettings->uen_no }}</strong></div>
            @endif
            <div>All cheques should be addressed to:</div>
            <div><strong>{{ strtoupper($companySettings->company_name ?? 'SG TRADE PTE. LTD.') }}</strong></div>
            @if(isset($companySettings) && $companySettings->bank_name)
            <div><strong>{{ $companySettings->bank_name }} ACC NO.: {{ $companySettings->bank_account_no ?? '' }}</strong></div>
            @endif
        </div>

        {{-- Center: QR code --}}
        <div class="footer-qr">
            <div class="qr-company-name">{{ strtoupper($companySettings->company_name ?? 'SG TRADE PTE. LTD.') }}</div>
            <img src="{{ asset('assets/images/sgtrade_printqr.jpeg') }}" alt="Scan to Pay QR">
            <div class="scan-label">SCAN TO PAY</div>
        </div>

        {{-- Right: unused amount --}}
        <div class="footer-right">
            @if($unusedAmount > 0)
            <div>UNUSED AMOUNT :</div>
            <div class="footer-right-amount">{{ $currencySymbol }}{{ number_format($unusedAmount, 2) }}</div>
            @else
            <div>FULLY ALLOCATED</div>
            <div class="footer-right-amount" style="color:#166534;">{{ $currencySymbol }}0.00</div>
            @endif
        </div>
    </div>

    {{-- ── SIGNATURES ── --}}
    <div class="signatures-row">
        <div class="sign-cell">
            @if(isset($companySettings->authorized_signature) && $companySettings->authorized_signature)
                <img src="{{ \Storage::disk('public_uploads')->url($companySettings->authorized_signature) }}" class="sign-image" alt="Signature">
            @endif
            <div class="sign-label">Authorized Signature (Issued By)</div>
        </div>
        <div class="sign-cell">
            <div class="sign-label">Customer Signature / Co. Stamp</div>
        </div>
    </div>

</div>

<script>
    window.onload = function () { window.print(); }
</script>
</body>
</html>
