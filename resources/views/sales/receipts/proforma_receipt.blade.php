<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma Invoice #{{ $pi->pi_number }}</title>
    <style>
    /* ── FONTS ── */
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

    /* ── RESET ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── SCREEN ── */
    body {
        font-family: 'Noto Sans', Arial, sans-serif;
        font-size: 10px;
        font-weight: bold;
        line-height: 1.5;
        width: 302px;
        margin: 0 auto;
        padding: 10px 9px 8px;
        background: #fff;
        color: #000;
    }

    /* ── HEADER ── */
    .store-name {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    .store-sub {
        text-align: center;
        font-size: 9px;
        line-height: 1.55;
    }
    .doc-banner {
        text-align: center;
        font-size: 11px;
        font-weight: bold;
        letter-spacing: 3px;
        text-transform: uppercase;
        margin: 3px 0;
    }

    /* ── DIVIDERS ── */
    hr          { border: none; margin: 4px 0; }
    hr.dash     { border-top: 1px dashed #000; }
    hr.solid    { border-top: 1px solid  #000; }
    hr.double   { border-top: 3px double #000; }

    /* ── META ROWS ── */
    .kv {
        display: flex;
        justify-content: space-between;
        font-size: 9px;
        line-height: 1.55;
    }
    .kv .k { font-weight: bold; white-space: nowrap; padding-right: 4px; }
    .kv .v { text-align: right; }

    /* ── ITEMS TABLE ── */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
        table-layout: fixed;
    }
    .items-table th {
        text-align: left;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px dashed #000;
        padding-bottom: 3px;
        overflow: hidden;
    }
    .items-table th.r { text-align: right; }
    .items-table th.c { text-align: center; }
    .items-table td {
        padding: 2px 0;
        vertical-align: top;
        overflow: hidden;
        word-break: break-word;
        font-size: 9px;
    }
    .items-table td.r { text-align: right; }
    .items-table td.c { text-align: center; }

    /* ── TOTALS ── */
    .tot-row {
        display: flex;
        justify-content: space-between;
        font-size: 9px;
        line-height: 1.65;
    }
    .grand-row {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        font-weight: bold;
        line-height: 1.8;
    }

    /* ── FOOTER ── */
    .footer {
        text-align: center;
        font-size: 9px;
        line-height: 1.65;
    }
    .footer .tagline { font-size: 10px; font-weight: bold; letter-spacing: 1px; margin: 2px 0; }

    /* ── NO-PRINT TOOLBAR ── */
    .no-print {
        display: block;
        width: 100%;
        padding: 8px;
        margin-bottom: 12px;
        background: #1f2937;
        color: #d1fae5;
        font-family: 'Noto Sans', Arial, sans-serif;
        font-size: 11px;
        font-weight: bold;
        border: none;
        cursor: pointer;
        letter-spacing: 1px;
    }

    /* ── PRINT ── */
    @media print {
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        body {
            width: 100% !important;
            margin: 0 !important;
            padding: 6px 8px 4px !important;
            font-family: 'Noto Sans', Arial, sans-serif !important;
            font-size: 10px !important;
            font-weight: bold !important;
            line-height: 1.5 !important;
        }

        .no-print { display: none !important; }

        /* Fallback — doPrint() injects exact height for auto-cut */
        @page { margin: 0; size: 80mm auto; }
    }
    </style>
</head>
<body>

    <button class="no-print" onclick="triggerPrint()">&#128438; PRINT RECEIPT</button>

    {{-- ── STORE HEADER ── --}}
    @php $cs = $companySettings ?? null; @endphp
    <div class="store-name">{{ $cs->company_name ?? 'BUSINESS NAME' }}</div>
    <div class="store-sub">
        {{ $cs->address ?? '' }}<br>
        @if($cs->phone_no ?? null)Tel: {{ $cs->phone_no }}@endif
        @if($cs->email ?? null) | {{ $cs->email }}@endif<br>
        @if($cs->uen_no ?? null)UEN: {{ $cs->uen_no }}@endif
        @if($cs->registration_no ?? null) | Reg: {{ $cs->registration_no }}@endif
    </div>

    <hr class="dash">
    <div class="doc-banner">&#9632;&nbsp;PROFORMA INVOICE&nbsp;&#9632;</div>
    <hr class="dash">

    {{-- ── META ── --}}
    <div class="kv">
        <span class="k">PI No     :</span>
        <span class="v">{{ $pi->pi_number }}</span>
    </div>
    <div class="kv">
        <span class="k">Date      :</span>
        <span class="v">{{ \Carbon\Carbon::parse($pi->pi_date)->format('d M Y') }}</span>
    </div>
    <div class="kv">
        <span class="k">Customer  :</span>
        <span class="v">{{ $pi->customer->name ?? 'Walk-in' }}</span>
    </div>
    @if($pi->salesperson ?? null)
    <div class="kv">
        <span class="k">Salesperson:</span>
        <span class="v">{{ $pi->salesperson->name }}</span>
    </div>
    @endif

    <hr class="dash">

    {{-- ── ITEMS TABLE ── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:48%">ITEM</th>
                <th style="width:14%" class="c">QTY</th>
                <th style="width:19%" class="r">PRICE</th>
                <th style="width:19%" class="r">AMT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pi->items as $item)
            <tr>
                <td colspan="4" style="padding-top:3px; font-size:9px;">
                    {{ trim(($item->product->sku ?? '') . ' ' . $item->item_description) }}
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="c">{{ $item->total_qty }}</td>
                <td class="r">{{ number_format($item->unit_price, 2) }}</td>
                <td class="r">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="solid">

    {{-- ── TOTALS ── --}}
    <div class="tot-row"><span>Subtotal</span><span>{{ number_format($pi->subtotal, 2) }}</span></div>

    @if($pi->discount_amount > 0)
    <div class="tot-row"><span>Discount</span><span>-{{ number_format($pi->discount_amount, 2) }}</span></div>
    @endif

    @if(isset($companySettings) && $companySettings->gst_include === 'Yes' && $pi->tax_amount > 0)
    <div class="tot-row"><span>GST</span><span>{{ number_format($pi->tax_amount, 2) }}</span></div>
    @endif

    <hr class="double">

    <div class="grand-row">
        <span>TOTAL</span>
        <span>{{ number_format($pi->net_total, 2) }}</span>
    </div>

    <hr class="dash">

    {{-- ── FOOTER ── --}}
    <div class="footer">
        <div class="tagline">&#10022; THANK YOU FOR THE BUSINESS &#10022;</div>
        <div style="font-size:9px; margin-top:2px;">This is a Proforma Invoice — not a tax document.</div>
        <hr class="dash" style="margin:4px 0;">
        <div style="font-size:9px;">Powered by <strong>Techgeeks POS</strong></div>
    </div>

</body>
<script>
/* ── auto-print on load with exact page height for auto-cut ── */
function triggerPrint() {
    /*
     * body on screen is 302px wide ≈ 80mm at 96 dpi.
     * scrollHeight here is therefore accurate for the print layout height.
     * Inject a fixed @page height so the thermal driver sees a page boundary
     * between prints and fires the auto-cutter (instead of rolling continuously).
     */
    var hMm = Math.ceil(document.body.scrollHeight * 25.4 / 96) + 6;
    var dynPage = document.getElementById('_dynPage');
    if (!dynPage) {
        dynPage = document.createElement('style');
        dynPage.id = '_dynPage';
        document.head.appendChild(dynPage);
    }
    dynPage.textContent = '@media print { @page { size: 80mm ' + hMm + 'mm; margin: 0; } }';
    window.print();
}

window.addEventListener('load', function() { triggerPrint(); });

window.addEventListener('afterprint', function() {
    var dynPage = document.getElementById('_dynPage');
    if (dynPage && dynPage.parentNode) { dynPage.parentNode.removeChild(dynPage); }
});
</script>
</html>
