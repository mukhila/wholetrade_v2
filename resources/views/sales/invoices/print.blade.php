<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page {
                size: auto;
                margin: 5mm;
            }
            html, body {
                width: 100%;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff;
                font-family: 'Courier New', Courier, monospace;
                font-size: 13px;
                line-height: 1.2;
            }
            .a4-container {
                width: 100% !important;
                max-width: 80ch !important;
                height: auto !important;
                min-height: auto !important;
                max-height: none !important;
                margin: 0 auto !important;
                padding: 10px !important;
                box-shadow: none !important;
                border: none !important;
                box-sizing: border-box !important;
                page-break-after: auto;
            }
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            line-height: 1.2;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .a4-container {
            width: 100%;
            max-width: 80ch;
            margin: auto;
            background: white;
            padding: 15px;
            position: relative;
        }

        /* ── TAX INVOICE TITLE ── */
        .tax-invoice-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            border: 1px dashed #000;
            padding: 4px 0;           
            text-transform: uppercase;
        }

        /* ── COMPANY HEADER ── */
        .company-header-table {
            width: 100%;
            border-collapse: collapse;
            border-left: 1px dashed #000;
            border-right: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .company-logo {
            width: 100px;
            padding: 5px;
            vertical-align: middle;
        }
        .company-logo img {
            max-width: 80px;
            max-height: 100px;
            object-fit: contain;
        }
        .company-name-block {
            padding-left: 10px;
            vertical-align: middle;
        }
        .company-name-text {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            line-height: 1.1;
            color: #000;
        }
        .company-address {
            font-size: 12px;
            font-weight: bold;
            margin-top: 4px;
        }

        /* ── LAYOUT TABLE ── */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }
        .layout-table td {
            vertical-align: top;
            padding: 0;
        }

        /* ── INVOICE INFO SECTION ── */
        .invoice-info-table {
            width: 100%;
            border-collapse: collapse;
            border-left: 1px dashed #000;
            border-right: 1px dashed #000;
        }
        .invoice-info-table td {
            vertical-align: top;
            padding: 6px 10px;
        }
        .bill-to-block {
            width: 55%;
        }
        .bill-to-label {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 2px;
        }
        .bill-to-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .bill-to-address {
            font-size: 12px;
            line-height: 1.4;
        }
        .invoice-meta {
            width: 40%;
        }
        .invoice-meta-row {
            margin-bottom: 3px;
            font-size: 12px;
            text-align: right;
        }
        .invoice-meta-label {
            font-weight: bold;
            margin-right: 6px;
        }
        .invoice-meta-value {
            display: inline-block;
            min-width: 100px;
            text-align: left;
        }

        /* ── ITEMS TABLE ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            border-left: 1px dashed #000;
            border-right: 1px dashed #000;
        }
        .items-table th, .items-table td {
            border: 1px dashed #000;
            padding: 4px 6px;
            font-size: 12px;
        }
        .items-table thead tr th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
            border-bottom: 1px dashed #000;
        }
        th.col-sno, td.col-sno     { width: 5%;  text-align: center; }
        th.col-desc, td.col-desc   { text-align: left; }
        th.col-qty, td.col-qty     { width: 10%;  text-align: center; }
        th.col-unit, td.col-unit   { width: 10%;  text-align: center; }
        th.col-price, td.col-price { width: 15%;  text-align: right; }
        th.col-nett, td.col-nett   { width: 15%;  text-align: right; font-weight: bold; }

        /* total row inside table */
        .table-total-row td {
            font-weight: bold;
            text-align: center;
        }
        .table-total-row .col-qty {
            text-align: center;
        }
        .table-total-row .col-nett {
            text-align: right;
        }

        /* ── AMOUNT IN WORDS + GRAND TOTAL ── */
        .amount-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px dashed #000;
            border-top: none;
        }
        .amount-table td {
            padding: 6px 8px;
            vertical-align: middle;
        }
        .amount-words {
            font-size: 12px;
            font-style: italic;
            width: 60%;
        }
        .grand-total-block {
            text-align: right;
            width: 40%;
        }
        .grand-total-label {
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-right: 10px;
        }
        .grand-total-value {
            font-size: 20px;
            font-weight: bold;
            display: inline-block;
        }

        /* ── FOOTER PAYMENT + QR ── */
        .footer-payment-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px dashed #000;
            border-top: none;
        }
        .footer-payment-table td {
            vertical-align: top;
            padding: 8px;
        }
        .footer-payment {
            width: 40%;
            font-size: 12px;
            line-height: 1.5;
        }
        .footer-qr {
            width: 30%;
            text-align: center;
        }
        .footer-qr .qr-company-name {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .footer-qr img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .footer-qr .scan-label {
            font-size: 10px;
            font-weight: bold;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .footer-outstanding {
            width: 30%;
            text-align: right;
            font-size: 13px;
            font-weight: bold;
        }
        .outstanding-amount {
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }

        /* ── PAYMENT STATUS BADGE ── */
        .payment-status-badge {
            display: inline-block;
            padding: 1px 5px;
            font-weight: bold;
            font-size: 11px;
            border: 1px dashed;
            border-radius: 2px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .payment-status-paid    { color: #000; border-color: #000; background: #fff; }
        .payment-status-unpaid  { color: #000; border-color: #000; background: #fff; }
        .payment-status-partial { color: #000; border-color: #000; background: #fff; }

        /* ── PAYMENT STAMP ── */
        .payment-stamp {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            width: 220px;
            opacity: 0.45;
            pointer-events: none;
            z-index: 10;
        }

        /* ── SIGNATURES ── */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px dashed #000;
            border-top: none;
        }
        .sign-cell {
            width: 50%;
            text-align: center;
            padding: 8px 4px;
            border-right: 1px dashed #000;
            vertical-align: bottom;
        }
        .sign-cell:last-child { border-right: none; }
        .sign-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
            border-top: 1px dashed #000;
            padding-top: 4px;
            display: inline-block;
            width: 80%;
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
    $rowsPerPage    = 22;
    $footerThreshold = 17;

    $items       = $invoice->items->values();
    $totalItems  = $items->count();
    $pages       = [];

    if ($totalItems <= $footerThreshold) {
        $pages[] = $items;
    } else {
        $fullPages = intdiv($totalItems, $rowsPerPage);
        $remaining = $totalItems % $rowsPerPage;

        for ($i = 0; $i < $fullPages; $i++) {
            $pages[] = $items->slice($i * $rowsPerPage, $rowsPerPage);
        }

        if ($remaining > 0 && $remaining <= $footerThreshold) {
            $pages[] = $items->slice($fullPages * $rowsPerPage);
        } else {
            if ($remaining > 0) {
                $pages[] = $items->slice($fullPages * $rowsPerPage);
            }
            $pages[] = collect([]);
        }
    }

    $totalChunks = count($pages);

    $totalQty       = $invoice->items->sum('total_qty');
    $currencySymbol = $invoice->currency->symbol ?? '$';
    $netTotal       = $invoice->net_total ?? 0;

    // Amount in words (inline, avoids redeclaration on multi-page)
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
             'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
             'Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    $n = (int) round($netTotal);
    $w = '';
    if ($n >= 1000) { $w .= $ones[intdiv($n,1000)].' Thousand '; $n %= 1000; }
    if ($n >= 100)  { $w .= $ones[intdiv($n,100)].' Hundred ';   $n %= 100;  }
    if ($n >= 20)   { $w .= $tens[intdiv($n,10)].' ';            $n %= 10;   }
    if ($n > 0)     { $w .= $ones[$n].' '; }
    $amountWords = trim($w) ? trim($w).' Dollars Only' : 'Zero Dollars Only';
@endphp

@foreach($pages as $pageIndex => $chunk)
<div class="a4-container" style="@if(!$loop->last) page-break-after: always; @endif">

    {{-- ── TAX INVOICE TITLE ── --}}
    <div class="tax-invoice-title">Tax Invoice</div>

    {{-- ── COMPANY HEADER ── --}}
    <table class="company-header-table">
        <tr>
            <td class="company-logo">
                @if(isset($companySettings) && $companySettings->logo)
                    <img src="{{ \Storage::disk('public_uploads')->url($companySettings->logo) }}" alt="Logo">
                @else
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Logo">
                @endif
            </td>
            <td class="company-name-block">
                <div class="company-name-text">
                    {{ $companySettings->company_name ?? 'SG TRADE PTE LTD' }}
                </div>
                <div class="company-address">
                    {{ $companySettings->address ?? '' }}
                    @if(isset($companySettings) && $companySettings->phone_no)
                        &nbsp;&nbsp;PHONE No : {{ $companySettings->phone_no }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

     {{-- ── INVOICE INFO ── --}}
    <table class="invoice-info-table">
        <tr>
            <td class="bill-to-block">
                <div class="bill-to-label">INVOICE TO:</div>
                <div class="bill-to-name">{{ $invoice->customer->name ?? 'N/A' }}</div>
                <div class="bill-to-address">
                    {!! nl2br(e($invoice->billing_address)) !!}<br>
                    @if($invoice->customer->phone ?? false)
                        {{ $invoice->customer->phone }}
                    @endif
                </div>
            </td>
            <td class="invoice-meta">
                <div class="invoice-meta-row">
                    <span class="invoice-meta-label">Invoice No :</span>
                    <span class="invoice-meta-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="invoice-meta-row">
                    <span class="invoice-meta-label">Invoice Date :</span>
                    <span class="invoice-meta-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('m/d/Y') }}</span>
                </div>
                <div class="invoice-meta-row">
                    <span class="invoice-meta-label">Sales Person :</span>
                    <span class="invoice-meta-value">{{ strtoupper($invoice->salesperson->name ?? '') }}</span>
                </div>
                <div class="invoice-meta-row" style="margin-top:4px;">
                    <span class="invoice-meta-label">Payment :</span>
                    <span class="invoice-meta-value">
                        @php $ps = $invoice->payment_status ?? 'unpaid'; @endphp
                        <span class="payment-status-badge payment-status-{{ $ps }}">{{ strtoupper($ps) }}</span>
                    </span>
                </div>
                @if($totalChunks > 1)
                <div class="invoice-meta-row" style="margin-top:4px; font-size:11px; color:#000;">
                    <span class="invoice-meta-label">Page:</span>
                    <span class="invoice-meta-value">{{ $pageIndex + 1 }} / {{ $totalChunks }}</span>
                </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── ITEMS TABLE ── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-sno">S.No</th>
                <th class="col-desc">Description of Goods</th>
                <th class="col-qty">QTY</th>
                <th class="col-unit">UNIT</th>
                <th class="col-price">UNIT PRICE</th>
                <th class="col-nett">NETT PRICE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $item)
            @php $globalIndex = $items->search($item) + 1; @endphp
            <tr>
                <td class="col-sno">{{ $globalIndex }}</td>
                <td class="col-desc">
                    {{ strtoupper($item->item_description ?: ($item->product->name ?? '')) }}
                    @if($item->product?->packSize)
                        <span style="font-size:10px;color:#000;display:block;margin-top:1px;">{{ $item->product->packSize->name }}</span>
                    @endif
                </td>
                <td class="col-qty">{{ number_format($item->total_qty, 0) }}</td>
                <td class="col-unit">{{ strtoupper($item->product->uom->name ?? 'PCS') }}</td>
                <td class="col-price">{{ number_format($item->unit_price, 2) }}</td>
                <td class="col-nett">${{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach

            {{-- Empty filler rows to push total to bottom on last page --}}
            @if($loop->last)
                @php
                    $fillerCount = max(0, $footerThreshold - $chunk->count());
                @endphp
                @for($f = 0; $f < $fillerCount; $f++)
                <tr style="height: 20px;">
                    <td class="col-sno"></td>
                    <td class="col-desc"></td>
                    <td class="col-qty"></td>
                    <td class="col-unit"></td>
                    <td class="col-price"></td>
                    <td class="col-nett"></td>
                </tr>
                @endfor
            @endif
        </tbody>
        @if($loop->last)
        <tfoot>
            <tr class="table-total-row">
                <td class="col-sno"></td>
                <td class="col-desc" style="text-align:right; font-weight:bold;">TOTAL</td>
                <td class="col-qty">{{ number_format($totalQty, 0) }}</td>
                <td class="col-unit"></td>
                <td class="col-price"></td>
                <td class="col-nett">${{ number_format($netTotal, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- ── SUMMARY FOOTER (last page only) ── --}}
    @if($loop->last)

    {{-- Amount in words + Grand Total --}}
    <table class="amount-table">
        <tr>
            <td class="amount-words">({{ $amountWords }})</td>
            <td class="grand-total-block">
                <div class="grand-total-label">TOTAL</div>
                <div class="grand-total-value">{{ $currencySymbol }}{{ number_format($netTotal, 2) }}</div>
                @if(($invoice->paid_amount ?? 0) > 0)
                <div style="font-size:11px; margin-top:3px; text-align:right; color:#000;">
                    Paid: {{ $currencySymbol }}{{ number_format($invoice->paid_amount, 2) }}&nbsp;&nbsp;
                    Balance: {{ $currencySymbol }}{{ number_format($invoice->balance_due ?? 0, 2) }}
                </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Payment Info + QR + Outstanding --}}
    <table class="footer-payment-table">
        <tr>
            {{-- Left: payment details --}}
            <td class="footer-payment">
                @if(isset($companySettings) && $companySettings->uen_no)
                <div><strong>Paynow UEN NUMBER : {{ $companySettings->uen_no }}</strong></div>
                @endif
                <div>All cheques should be addressed to:</div>
                <div><strong>{{ strtoupper($companySettings->company_name ?? 'SG TRADE PTE. LTD.') }}</strong></div>
                @if(isset($companySettings) && $companySettings->bank_name)
                <div><strong>{{ $companySettings->bank_name }} ACC NO.: {{ $companySettings->bank_account_no ?? '' }}</strong></div>
                @endif
                @if(isset($companySettings) && $companySettings->company_name)
                <div style="margin-top:4px;"><strong>{{ strtoupper($companySettings->company_name) }}</strong></div>
                @endif
            </td>

            {{-- Center: QR code --}}
            <td class="footer-qr">
                <div class="qr-company-name">{{ strtoupper($companySettings->company_name ?? 'SG TRADE PTE. LTD.') }}</div>
                <img src="{{ asset('assets/images/sgtrade_printqr.jpeg') }}" alt="Scan to Pay QR">
                <div class="scan-label">SCAN TO PAY</div>
            </td>

            {{-- Right: outstanding --}}
            <td class="footer-outstanding">
                <div>TOTAL OUTSTANDING :</div>
                <div class="outstanding-amount">
                    {{ $currencySymbol }}{{ number_format($invoice->balance_due ?? $netTotal, 2) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ── PAID / UNPAID STAMP ── --}}
    @php $ps = strtolower($invoice->payment_status ?? 'unpaid'); @endphp
    @if($ps === 'paid')
        <img src="{{ asset('assets/images/paid.png') }}" class="payment-stamp" alt="PAID">
    @else
        <img src="{{ asset('assets/images/unpaid.png') }}" class="payment-stamp" alt="UNPAID">
    @endif

    {{-- Signatures --}}
    <table class="signatures-table">
        <tr>
            <td class="sign-cell">
                @if(isset($companySettings->authorized_signature) && $companySettings->authorized_signature)
                    <img src="{{ \Storage::disk('public_uploads')->url($companySettings->authorized_signature) }}" class="sign-image" alt="Signature">
                @endif
                <div class="sign-label">Authorized Signature (Issue By)</div>
            </td>       
            <td class="sign-cell">
                <div class="sign-label">Customer Signature / Co. Stamp</div>
            </td>
        </tr>
    </table>

    @endif

    {{-- Continued label for non-last pages --}}
    @if(!$loop->last)
    <div style="text-align: right; font-style: italic; font-size: 11px; margin-top: 8px;">
        Continued on next page...
    </div>
    @endif

</div>
@endforeach

<script>
    window.onload = function() { window.print(); }
</script>
</body>
</html>
