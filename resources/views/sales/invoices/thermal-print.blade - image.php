<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Thermal Receipt — {{ $invoice->invoice_number }}</title>
<style>
/* ============================================================
   RESET
   ============================================================ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ============================================================
   SCREEN PREVIEW SHELL
   ============================================================ */
body {
  background: #111827;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px 12px 56px;
  font-family: 'Noto Sans', Arial, sans-serif;
}

.toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  justify-content: center;
}
.tlabel {
  color: #4b5563;
  font-size: 10px;
  font-family: 'Noto Sans', Arial, sans-serif;
  letter-spacing: 1px;
}
.btn {
  font-family: 'Noto Sans', Arial, sans-serif;
  font-size: 11px;
  padding: 6px 14px;
  border: 1px solid #374151;
  background: #1f2937;
  color: #9ca3af;
  cursor: pointer;
  border-radius: 3px;
  letter-spacing: 0.5px;
  transition: background .15s, color .15s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.btn:hover  { background: #374151; color: #f3f4f6; }
.btn.active { background: #1d4ed8; border-color: #3b82f6; color: #fff; }
.btn-back   { background: #1f2937; border-color: #6b7280; color: #9ca3af; }

/* ── print action buttons ── */
.btn-direct  { background: #065f46; border-color: #059669; color: #d1fae5; font-weight: bold; font-size: 12px; padding: 7px 18px; }
.btn-direct:hover:not(:disabled) { background: #047857; }
.btn-direct:disabled { opacity: .45; cursor: not-allowed; }
.btn-rawbt   { background: #1e3a5f; border-color: #3b82f6; color: #93c5fd; font-weight: bold; font-size: 12px; padding: 7px 18px; }
.btn-rawbt:hover { background: #1d4ed8; color: #fff; }
.btn-rawbt.unavailable { background: #1f2937; border-color: #4b5563; color: #6b7280; cursor: default; }
.btn-browser { background: #374151; border-color: #6b7280; color: #d1d5db; padding: 7px 14px; }
.btn-browser:hover { background: #4b5563; color: #fff; }

.toolbar-sep { width: 1px; height: 26px; background: #374151; margin: 0 2px; flex-shrink: 0; }

.copies-wrap { display: flex; align-items: center; gap: 5px; }
.copies-input {
  width: 42px; padding: 5px 6px;
  border: 1px solid #374151; background: #1f2937;
  color: #f3f4f6; border-radius: 3px; font-size: 12px;
  text-align: center; outline: none;
  font-family: 'Noto Sans', Arial, sans-serif;
}

/* ── status bar below toolbar ── */
#printStatusBar {
  display: none;
  text-align: center;
  font-size: 11px;
  font-family: 'Noto Sans', Arial, sans-serif;
  padding: 5px 14px;
  border-radius: 4px;
  margin-bottom: 14px;
  min-width: 220px;
  letter-spacing: .3px;
}
#printStatusBar.ok   { background: #064e3b; color: #6ee7b7; border: 1px solid #059669; }
#printStatusBar.err  { background: #7f1d1d; color: #fca5a5; border: 1px solid #dc2626; }
#printStatusBar.info { background: #1c3456; color: #93c5fd; border: 1px solid #3b82f6; }

/* ── RawBT info tooltip ── */
.rawbt-tip {
  position: relative; display: inline-flex;
}
.rawbt-tip .tip-box {
  display: none;
  position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
  margin-top: 6px; background: #1f2937; border: 1px solid #374151;
  border-radius: 4px; padding: 7px 10px; white-space: nowrap;
  font-size: 10px; color: #9ca3af; z-index: 99; text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,.5);
  pointer-events: none;
}
.rawbt-tip:hover .tip-box { display: block; }

/* printer body */
.printer-body {
  background: #1f2937;
  border-radius: 8px 8px 0 0;
  padding: 12px 14px 0;
  box-shadow: 0 2px 0 #374151;
  max-width: calc(100vw - 24px);
}
.printer-body::before {
  content: '● ● ●';
  display: block;
  color: #374151;
  font-size: 10px;
  letter-spacing: 6px;
  text-align: center;
  margin-bottom: 8px;
}
.printer-slot {
  background: #0f172a;
  height: 6px;
  border-radius: 3px;
}
.paper-frame {
  background: #374151;
  border-radius: 0 0 6px 6px;
  padding: 0 6px 10px;
  box-shadow: 0 16px 48px rgba(0,0,0,0.65);
  max-width: calc(100vw - 24px);
}

/* ============================================================
   RECEIPT PAPER
   ============================================================ */
.receipt {
  width: 320px;
  max-width: calc(100vw - 24px);
  background: #fefefe;
  background-image:
    repeating-linear-gradient(
      180deg,
      transparent 0px,
      transparent 2px,
      rgba(0,0,0,0.016) 2px,
      rgba(0,0,0,0.016) 3px
    );
  padding: 5px 8px;
  font-family: Arial, sans-serif;
  font-size: 11px;
  color: #000;
  line-height: 1.42;
  position: relative;
}
.receipt::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255,250,225,0.05);
  pointer-events: none;
}

/* ============================================================
   TYPOGRAPHY
   ============================================================ */
.tc  { text-align: center; }
.tr  { text-align: right; }
.b   { font-weight: bold; }
.xs  { font-size: 10px; line-height: 1.5; }
.sm  { font-size: 10px; }
.mt2 { margin-top: 2px; }
.mt4 { margin-top: 4px; }
.mt6 { margin-top: 6px; }
.mt8 { margin-top: 8px; }

/* ============================================================
   DIVIDERS
   ============================================================ */
hr          { border: none; margin: 5px 0; }
hr.dash     { border-top: 1px dashed #000; }
hr.solid    { border-top: 1px solid  #000; }
hr.double   { border-top: 3px double #000; }

/* ============================================================
   STORE HEADER
   ============================================================ */
.store-name {
  text-align: center;
  font-size: 15px;
  font-weight: bold;
  letter-spacing: 2px;
  text-transform: uppercase;
}
.store-sub {
  text-align: center;
  font-size: 10px;
  line-height: 1.55;
}
.inv-banner {
  text-align: center;
  font-size: 12px;
  font-weight: bold;
  letter-spacing: 3px;
  text-transform: uppercase;
  margin: 3px 0;
}

/* ============================================================
   META ROWS
   ============================================================ */
.kv {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  line-height: 1.55;
}
.kv .k { font-weight: bold; white-space: nowrap; padding-right: 3px; }
.kv .v { text-align: right; font-size: 10px; }

/* ============================================================
   ITEMS TABLE
   ============================================================ */
.t-items {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
  table-layout: fixed;
}
.t-items colgroup col:nth-child(1) { width: 45%; }
.t-items colgroup col:nth-child(2) { width: 15%; }
.t-items colgroup col:nth-child(3) { width: 20%; }
.t-items colgroup col:nth-child(4) { width: 20%; }

.t-items thead th {
  font-size: 10px;
  font-weight: bold;
  text-align: left;
  padding: 1px 0;
}
.t-items thead th.r { text-align: right; }

.t-items tbody td { padding: 0; vertical-align: top; }
.t-items tbody td.r { text-align: right; }
.t-items tbody td.c { text-align: center; }

.item-code { font-weight: bold; font-size: 10px; padding: 2px 0 0; white-space: normal; word-break: break-word; overflow-wrap: break-word; }
.item-desc { font-size: 9px;  color: #555;  padding: 0 0 1px 0; }
.item-vals td { padding-bottom: 3px; }

.item-sep td {
  border-top: 1px dotted #ccc;
  font-size: 1px;
  line-height: 3px;
  height: 3px;
  padding: 1px 0;
}

/* ============================================================
   TOTALS
   ============================================================ */
.tot-row {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  line-height: 1.65;
}
.tot-row.muted { color: #000; }
.grand-row {
  display: flex;
  justify-content: space-between;
  font-size: 15px;
  font-weight: bold;
  line-height: 1.8;
}
.outstanding-row {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  font-weight: bold;
  line-height: 1.7;
  border-left: 3px solid #000;
  padding-left: 5px;
  margin: 2px 0;
}

/* ============================================================
   GST SUMMARY
   ============================================================ */
.gst-head {
  font-size: 10px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 2px;
}
.t-gst {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
}
.t-gst th, .t-gst td {
  padding: 1px 2px;
  text-align: right;
}
.t-gst th:first-child,
.t-gst td:first-child { text-align: left; }
.t-gst thead tr { border-bottom: 1px solid #000; }
.t-gst tfoot tr { border-top: 1px solid #000; font-weight: bold; }

/* ============================================================
   PAYMENT SECTION
   ============================================================ */
.pay-section { font-size: 10px; line-height: 1.6; padding: 1px 0; }
.pay-title {
  font-size: 10px;
  font-weight: bold;
  text-decoration: underline;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 2px;
}

/* ============================================================
   SIGNATURES
   ============================================================ */
.sig-wrap { display: flex; gap: 10px; margin-top: 10px; }
.sig-box  { flex: 1; text-align: center; }
.sig-line { border-bottom: 1px solid #000; height: 24px; margin-bottom: 3px; }
.sig-lbl  { font-size: 10px; line-height: 1.4; }

/* ============================================================
   BARCODE
   ============================================================ */
.barcode-wrap { text-align: center; margin: 4px 0 2px; }
.barcode {
  display: inline-flex;
  align-items: stretch;
  height: 38px;
}
.barcode span            { display: inline-block; }
.barcode span.f          { background: #000; height: 100%; }
.barcode span.s          { background: #000; height: 80%; align-self: flex-end; }
.barcode span.w          { background: transparent; height: 100%; }
.barcode-num {
  font-size: 9px;
  letter-spacing: 4px;
  text-align: center;
  margin-top: 2px;
  font-weight: bold;
}

/* ============================================================
   FOOTER
   ============================================================ */
.footer { text-align: center; font-size: 10px; line-height: 1.65; }
.footer .tagline { font-size: 11px; font-weight: bold; letter-spacing: 1px; margin: 2px 0; }

/* ============================================================
   CUT LINE
   ============================================================ */
.cut-line {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  font-size: 10px;
  color: #000;
}
.cut-line::before, .cut-line::after {
  content: '';
  flex: 1;
  border-top: 1px dashed #888;
}
.cut-sym { font-size: 13px; color: #222; }

/* ============================================================
   STATUS BADGE
   ============================================================ */
.status-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: bold;
  padding: 1px 4px;
  border: 1px solid #000;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* ============================================================
   PRINT
   ============================================================ */
@media print {
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

  .status-badge {
    background: transparent !important;
    border-color: #000 !important;
    color: #000 !important;
  }														   
  body  {
    background: none !important;
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
  }
  .toolbar        { display: none !important; }
  #printStatusBar { display: none !important; }
  .printer-body   { display: none !important; }
  .printer-slot   { display: none !important; }
  .paper-frame    { all: unset !important; display: block !important; }
  .cut-line       { display: none !important; }

  .receipt {
    background: #fff !important;
    background-image: none !important;
    width: 72mm !important;
    max-width: 72mm !important;
    padding: 0 !important;
    box-shadow: none !important;
    font-family: Arial, sans-serif !important;
    page-break-after: always;
  }
  .receipt::after { display: none !important; }

  @page { margin: 4mm; size: 80mm auto; }
}

/* ============================================================
   DEBUG LOG PANEL  (visible only with ?debug=1)
   ============================================================ */
#tpDebugPanel {
  position: fixed;
  left: 0; right: 0; bottom: 0;
  max-height: 45vh;
  overflow-y: auto;
  background: #000;
  color: #4ade80;
  font-family: 'Courier New', monospace;
  font-size: 10px;
  line-height: 1.5;
  padding: 6px 10px 10px;
  z-index: 99999;
  border-top: 2px solid #4ade80;
  display: none;
}
#tpDebugPanel .tp-err  { color: #f87171; font-weight: bold; }
#tpDebugPanel .tp-head { color: #fbbf24; font-weight: bold; }
@media print { #tpDebugPanel { display: none !important; } }
</style>

<script>
/* ============================================================
   THERMAL PRINT — STEP-BY-STEP DEBUG LOGGER
   - Always logs to browser console
   - Always ships each step to the server -> storage/logs/laravel.log
     (search for "THERMAL-PRINT" in the log file via cPanel)
   - Add ?debug=1 to the URL to ALSO see the log panel on screen
     (useful on mobile/tablet where there are no devtools)
   ============================================================ */
(function () {
  window.TP_DEBUG = /[?&]debug=1/.test(window.location.search);

  var LOG_URL = "{{ route('sales-invoices.thermal-print.client-log') }}";
  var CSRF    = "{{ csrf_token() }}";
  var INVOICE = {{ Js::from($invoice->invoice_number) }};
  var seq     = 0;

  function panel() {
    var p = document.getElementById('tpDebugPanel');
    if (!p && document.body) {
      p = document.createElement('div');
      p.id = 'tpDebugPanel';
      p.innerHTML = '<div class="tp-head">THERMAL PRINT DEBUG LOG — invoice ' + INVOICE + ' (remove ?debug=1 to hide)</div>';
      document.body.appendChild(p);
    }
    if (p && window.TP_DEBUG) p.style.display = 'block';
    return p;
  }

  window.tpLog = function (step, detail, isError) {
    seq++;
    var time = new Date().toTimeString().split(' ')[0];
    var line = '#' + seq + ' [' + time + '] ' + step + (detail ? ' | ' + detail : '');

    /* 1) console */
    try { (isError ? console.error : console.log)('[THERMAL-PRINT] ' + line); } catch (e) {}

    /* 2) on-screen panel (?debug=1) */
    try {
      var p = panel();
      if (p) {
        var d = document.createElement('div');
        if (isError) d.className = 'tp-err';
        d.textContent = line;
        p.appendChild(d);
        p.scrollTop = p.scrollHeight;
      }
    } catch (e) {}

    /* 3) ship to server -> laravel.log */
    try {
      fetch(LOG_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF,
          'Accept': 'application/json'
        },
        credentials: 'same-origin',
        keepalive: true,
        body: JSON.stringify({ step: line, detail: detail || null, invoice: INVOICE })
      }).then(function (r) {
        if (!r.ok) window.tpLogLocal('SERVER LOG FAILED: HTTP ' + r.status + ' (check route/CSRF/session)');
      }).catch(function (err) {
        window.tpLogLocal('SERVER LOG FAILED: ' + err.message);
      });
    } catch (e) {}
  };

  /* logs only console+panel (no server) to avoid infinite loop */
  window.tpLogLocal = function (msg) {
    try { console.warn('[THERMAL-PRINT] ' + msg); } catch (e) {}
    try {
      var p = panel();
      if (p) {
        var d = document.createElement('div');
        d.className = 'tp-err';
        d.textContent = msg;
        p.appendChild(d);
      }
    } catch (e) {}
  };

  /* catch ALL javascript errors on this page */
  window.onerror = function (msg, src, line, col) {
    window.tpLog('B-ERROR: JavaScript error', msg + ' @ ' + (src || '?') + ':' + line + ':' + col, true);
  };

  window.tpLog('B1: HTML received, logger script running', 'url=' + window.location.pathname + window.location.search);

  document.addEventListener('DOMContentLoaded', function () {
    window.tpLog('B2: DOM ready (receipt HTML fully parsed)');
  });
  window.addEventListener('load', function () {
    window.tpLog('B3: Page fully loaded (fonts/assets done) — ready to print');
  });
})();
</script>
</head>
<body>

{{-- ── TOOLBAR ── --}}
<div class="toolbar" id="toolbar">

  {{-- back --}}
  <a class="btn btn-back" href="{{ url()->previous() }}">&#8592; Back</a>

  <div class="toolbar-sep"></div>

  {{-- paper width --}}
  <span class="tlabel">PAPER:</span>
  <button class="btn active" id="btn80" onclick="setWidth(80)">80 mm</button>
  <button class="btn"        id="btn58" onclick="setWidth(58)">58 mm</button>

  <div class="toolbar-sep"></div>

  {{-- copies --}}
  <div class="copies-wrap">
    <span class="tlabel">COPIES:</span>
    <input type="number" id="copiesInput" class="copies-input" value="1" min="1" max="10">
  </div>

  <div class="toolbar-sep"></div>

  {{-- ⚡ Direct Print — no dialog, goes via shop bridge client --}}
  <button class="btn btn-direct" id="btnBridge" onclick="directPrint()">
    &#9889; Direct Print
  </button>

  {{-- 📱 Bluetooth / RawBT --}}
  <div class="rawbt-tip">
    <a class="btn btn-rawbt unavailable" id="btnRawbt" href="#">
      &#128241; Bluetooth
    </a>
    <div class="tip-box" id="rawbtTip">
      Opens RawBT app on Android<br>
      to print via Bluetooth printer.<br>
      <span style="color:#6b7280">Not available on this device.</span>
    </div>
  </div>

  {{-- 🖨 Browser Print — standard OS dialog --}}
  <button class="btn btn-browser" onclick="doPrint()">
    &#128438; Browser Print
  </button>

</div>

{{-- status bar (shown after print actions) --}}
<div id="printStatusBar"></div>

{{-- ── PRINTER SHELL ── --}}
<div class="printer-body" id="printerBody">
  <div class="printer-slot"></div>
</div>

<div class="paper-frame" id="paperFrame">
<div class="receipt" id="receipt">

  @php
    $cs  = $companySettings;
    $sym = $invoice->currency->symbol ?? '$';
  @endphp

  {{-- ── STORE HEADER ── --}}
  <div class="store-name">{{ $cs->company_name ?? 'BUSINESS NAME' }}</div>
  <div class="store-sub mt2">
    {{ $cs->address ?? '' }}<br>
    @if($cs->phone_no ?? null)Ph: {{ $cs->phone_no }}@endif
    @if($cs->registration_no ?? null) | Co.Reg: {{ $cs->registration_no }}@endif<br>
    @if($cs->email ?? null){{ $cs->email }}@endif
    @if($cs->uen_no ?? null) | UEN: {{ $cs->uen_no }}@endif
  </div>

  <hr class="dash">

   {{-- ── INVOICE BANNER ── --}}
<div class="inv-banner">&#9632;&nbsp;{{ $invoice->tax_amount > 0 ? 'TAX INVOICE' : 'INVOICE' }}&nbsp;&#9632;</div>

  <hr class="dash">

  {{-- ── META ── --}}
  <div class="kv">
    <span class="k">Invoice No  :</span>
    <span class="v">{{ $invoice->invoice_number }}</span>
  </div>
  <div class="kv">
    <span class="k">Date        :</span>
    <span class="v">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</span>
  </div>

  <div class="kv">
    <span class="k">Customer    :</span>
    <span class="v">{{ $invoice->customer->name ?? 'WALK-IN' }}</span>
  </div>
  @if(($invoice->customer->address_line_1 ?? '') || ($invoice->customer->address_line_2 ?? ''))
  <div class="kv"><span class="k">Address    :</span></div>
  @if($invoice->customer->address_line_1 ?? '')
  <div class="xs" style="padding-left:4px">{{ $invoice->customer->address_line_1 }}</div>
  @endif
  @if($invoice->customer->address_line_2 ?? '')
  <div class="xs" style="padding-left:4px">{{ $invoice->customer->address_line_2 }}</div>
  @endif
  @endif
  @if($invoice->salesperson)
  <div class="kv">
    <span class="k">Salesperson :</span>
    <span class="v">{{ $invoice->salesperson->name }}</span>
  </div>
  @endif
  @if($invoice->reference_number)
  <div class="kv">
    <span class="k">Reference   :</span>
    <span class="v">{{ $invoice->reference_number }}</span>
  </div>
  @endif
@php $ps = $invoice->payment_status ?? 'unpaid'; @endphp
  <div class="kv mt2">
    <span class="k">Status      :</span>
    <span class="v">
      <span class="status-badge" style="
        @if($ps === 'paid') border-color:#166534; color:#166534; background:#dcfce7;
        @elseif($ps === 'partial') border-color:#92400e; color:#92400e; background:#fef3c7;
        @else border-color:#991b1b; color:#991b1b; background:#fee2e2;
        @endif
      ">{{ strtoupper($ps) }}</span>
    </span>
  </div>

  <hr class="dash">

  {{-- ── ITEMS TABLE ── --}}
  <table class="t-items">
    <colgroup><col><col><col><col></colgroup>
    <thead>
      <tr>
        <th>DESCRIPTION</th>
        <th class="r">QTY</th>
        <th class="r">PRICE</th>
        <th class="r">AMT</th>
      </tr>
      <tr class="item-sep"><td colspan="4"></td></tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $idx => $item)
        @php
          $desc    = $item->item_description ?: ($item->product->name ?? '');
          $uomName = $item->product->uom->name ?? '';
          $boxNote = ($item->box_qty ?? 0) > 0 ? $item->box_qty . ' box' : '';
          $subNote = implode(' | ', array_filter([$uomName, $boxNote]));
          $qty     = $item->total_qty;
          $qtyFmt  = ($qty == floor($qty)) ? (int)$qty : number_format($qty, 2);
          $hasDisc = ($item->discount_amount ?? 0) > 0;
        @endphp

        @if($idx > 0)
        <tr class="item-sep"><td colspan="4"></td></tr>
        @endif

        {{-- Row 1: product name spans full width --}}
        <tr>
          <td colspan="4" class="item-code">{{ $desc }}</td>
        </tr>

        {{-- Row 2: UOM / box note (optional) --}}
        @if($subNote)
        <tr>
          <td colspan="4" class="item-desc">{{ $subNote }}</td>
        </tr>
        @endif

        {{-- Row 3: values — description col empty, then QTY | PRICE | AMT --}}
        <tr class="item-vals">
          <td></td>
          <td class="r">{{ $qtyFmt }}</td>
          <td class="r">{{ number_format($item->unit_price, 2) }}</td>
          <td class="r">{{ number_format($item->total_price, 2) }}</td>
        </tr>

        @if($hasDisc)
        <tr>
          <td colspan="3" class="item-desc">Discount</td>
          <td class="r item-desc">-{{ number_format($item->discount_amount, 2) }}</td>
        </tr>
        @endif

      @endforeach
    </tbody>
  </table>

  <hr class="solid">

  {{-- ── TOTALS ── --}}
  <div class="tot-row"><span>Sub Total</span><span>{{ number_format($invoice->subtotal, 2) }}</span></div>

  @if($invoice->discount_amount > 0)
  <div class="tot-row muted"><span>Discount</span><span>-{{ number_format($invoice->discount_amount, 2) }}</span></div>
  @endif

  @if($invoice->tax_amount > 0)
 @php
    $taxBase = max(0, ($invoice->subtotal ?? 0) - ($invoice->discount_amount ?? 0));
    $gstRate = $taxBase > 0 ? round($invoice->tax_amount / $taxBase * 100) : 0;
  @endphp
  <div class="tot-row"><span>GST ({{ $gstRate }}%)</span><span>{{ number_format($invoice->tax_amount, 2) }}</span></div>
  @endif


  <hr class="double">

  <div class="grand-row">
    <span>Net Total ({{ $sym }})</span>
    <span>{{ number_format($invoice->net_total, 2) }}</span>
  </div>


 @if(($invoice->paid_amount ?? 0) > 0)
  <hr class="dash">
  <div class="tot-row"><span>Paid Amount</span><span>{{ number_format($invoice->paid_amount, 2) }}</span></div>
  <div class="tot-row b"><span>Balance Due</span><span>{{ number_format($invoice->balance_due, 2) }}</span></div>
  @endif

  <hr class="dash">

  {{-- ── THIS INVOICE / OUTSTANDING ── --}}
  <!--div class="tot-row b"><span>THIS INVOICE&nbsp;&nbsp;:</span><span>{{ $sym }}&nbsp;{{ number_format($invoice->balance_due, 2) }}</span></div-->
  @if(isset($customerOutstandingBalance) && $customerOutstandingBalance > 0)
  <div class="outstanding-row">
    <span>TOTAL OUTSTANDING:</span>
    <span>{{ $sym }}&nbsp;{{ number_format($customerOutstandingBalance, 2) }}</span>
  </div>
  @endif

  @if($invoice->remarks)
  <hr class="dash">
  <div class="xs b">Remarks:</div>
  <div class="xs mt2">{{ $invoice->remarks }}</div>
  @endif

  <hr class="dash">

  {{-- ── PAYMENT INSTRUCTIONS ── --}}
  <div class="pay-section">
    <div class="pay-title">Payment Instructions</div>
    <div class="xs mt2">All cheques crossed &amp; payable to:</div>
    <div class="xs b mt2">{{ strtoupper($cs->company_name ?? 'BUSINESS NAME') }}</div>
    @if(($cs->bank_name ?? null) || ($cs->bank_account_no ?? null))
    <div class="xs b mt4">BANK TRANSFER:</div>
    @if($cs->bank_name ?? null)
    <div class="xs">Bank&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $cs->bank_name }}</div>
    @endif
    @if($cs->bank_account_no ?? null)
    <div class="xs">Acct No&nbsp;&nbsp;: {{ $cs->bank_account_no }}</div>
    @endif
    @if($cs->uen_no ?? null)
    <div class="xs">UEN No&nbsp;&nbsp;&nbsp;: {{ $cs->uen_no }}</div>
    @endif
    @endif
  </div>

		  {{-- ── QR CODE (only shown when image exists on server) ── --}}
  @php $qrPath = public_path('assets/images/sgtrade_printqr.jpeg'); @endphp
  @if(file_exists($qrPath))
  <!--hr class="dash">
  <div style="text-align:center; padding: 4px 0 2px;">
    <div style="font-size:10px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; margin-bottom:4px;">
      {{ strtoupper($cs->company_name ?? '') }}
    </div>
    <img src="{{ asset('assets/images/sgtrade_printqr.jpeg') }}"
         alt="Scan to Pay QR"
         style="width:100px; height:100px; object-fit:contain; display:block; margin:0 auto;">
    <div style="font-size:10px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; margin-top:4px;">
      &#9632; SCAN TO PAY &#9632;
    </div>
    @if($cs->uen_no ?? null)
    <div style="font-size:10px; margin-top:2px;">PayNow UEN: {{ $cs->uen_no }}</div>
    @endif
  </div-->
  @endif										   
  <!--hr class="dash"-->
  {{-- ── SIGNATURES ── --}}
  <!--div class="sig-wrap">
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-lbl">Authorized Signature</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-lbl">Customer Sign<br>&amp; Co. Stamp</div>
    </div>
  </div-->

  <!--hr class="dash" style="margin-top:8px;"-->

  {{-- ── BARCODE ── --}}
  <!--div class="barcode-wrap">
    <div class="barcode" id="barcode"></div>
    <div class="barcode-num">{{ $invoice->invoice_number }}</div>
  </div-->

  <!--hr class="dash"-->

  {{-- ── FOOTER ── --}}
  <div class="footer">
    <!--div class="tagline">&#10022; THANK YOU FOR YOUR BUSINESS &#10022;</div-->
   
    <!--hr class="dash" style="margin:4px 0;"-->
    <!--div class="xs b">Return Policy</div>
    <div class="xs">Returns within 7 days with original receipt.</div>
    <div class="xs">Items must be unused &amp; in original packaging.</div-->
    <hr class="dash" style="margin:4px 0;">
     <div class="xs">Powered by <span class="b">Techgeeks</span></div>

  {{-- ── CUT LINE ── --}}
  <div class="cut-line" id="cutLine">
    <span class="cut-sym">&#9988;</span>
    <span style="white-space:nowrap; font-size:9px;">CUT HERE</span>
  </div>

</div>{{-- /receipt --}}
</div>{{-- /paper-frame --}}

<script>
/* ── width toggle ── */
function setWidth(mm) {
  _mm = mm;
  var idealPx = mm === 58 ? 248 : 320;
  var maxPx   = Math.floor(window.innerWidth - 24);   // keep within viewport
  var shellPx = Math.min(idealPx + 28, maxPx);
  var rcptPx  = Math.min(idealPx, maxPx - 12);
  var r  = document.getElementById('receipt');
  var pb = document.getElementById('printerBody');
  var pf = document.getElementById('paperFrame');
  r.style.width  = rcptPx + 'px';
  pb.style.width = shellPx + 'px';
  pf.style.width = shellPx + 'px';
  document.getElementById('btn80').classList.toggle('active', mm === 80);
  document.getElementById('btn58').classList.toggle('active', mm === 58);
  try { localStorage.setItem('tp_paper_mm', String(mm)); } catch (e) {}
  if (window.updateRawbtHref) window.updateRawbtHref();   // RawBT payload must match paper width
  tpLog('B4: Paper width changed', mm + 'mm (ideal=' + idealPx + 'px, actual=' + rcptPx + 'px)');
}

/* ── print with step logging ── */
function doPrint() {
  tpLog('B6: Print button clicked');

  if (typeof window.print !== 'function') {
    tpLog('B6-ERROR: window.print is NOT available in this browser/webview', navigator.userAgent, true);
    alert('This browser does not support printing. Please open this page in Chrome/Safari.');
    return;
  }

  tpLog('B7: Calling window.print() — print dialog should open NOW');
  try {
    window.print();
    tpLog('B8: window.print() returned — dialog was opened (user picks printer here)');
  } catch (e) {
    tpLog('B7-ERROR: window.print() threw an error', e.message, true);
  }
}

/* ── CSS barcode ──
   Encodes each char into alternating black bars / white gaps.
   Width of each element driven by bit-fields of the char code.
─────────────────────────────────────────────────────────────── */
(function buildBarcode() {
  var code = {{ Js::from($invoice->invoice_number) }};
  var el   = document.getElementById('barcode');
  if (!el) { tpLog('B5-ERROR: barcode element not found', null, true); return; }

  function charBars(ch) {
    var v = ch.charCodeAt(0);
    return [
      { d: true,  w: (v & 0x03) + 1 },
      { d: false, w: 1 },
      { d: true,  w: ((v >> 2) & 0x01) + 1 },
      { d: false, w: ((v >> 3) & 0x01) + 1 },
      { d: true,  w: ((v >> 4) & 0x03) + 1 },
      { d: false, w: 1 }
    ];
  }

  var bars = [];
  /* start guard */
  bars.push({ d:true,  w:2, c:'f' });
  bars.push({ d:false, w:1, c:'w' });
  bars.push({ d:true,  w:1, c:'f' });
  bars.push({ d:false, w:1, c:'w' });

  for (var i = 0; i < code.length; i++) {
    var parts = charBars(code[i]);
    for (var j = 0; j < parts.length; j++) {
      bars.push({
        d: parts[j].d,
        w: parts[j].w,
        c: parts[j].d ? (parts[j].w === 1 ? 's' : 'f') : 'w'
      });
    }
  }

  /* end guard */
  bars.push({ d:false, w:1, c:'w' });
  bars.push({ d:true,  w:1, c:'f' });
  bars.push({ d:false, w:1, c:'w' });
  bars.push({ d:true,  w:2, c:'f' });

  var html = '';
  for (var k = 0; k < bars.length; k++) {
    html += '<span class="' + bars[k].c + '" style="width:' + bars[k].w + 'px"></span>';
  }
  el.innerHTML = html;
  tpLog('B5: Barcode rendered', bars.length + ' bars for ' + code);
}());

/* ── current paper width (updated by setWidth) ── */
var _mm = 80;

/* ── status bar helper ── */
function showStatus(msg, type) {
  var bar = document.getElementById('printStatusBar');
  bar.textContent  = msg;
  bar.className    = type || 'info'; // ok | err | info
  bar.style.display = 'block';
  if (type === 'ok') setTimeout(function () { bar.style.display = 'none'; }, 5000);
}

/* ── DIRECT PRINT via shop print-bridge (server queue, no dialog) ── */
var DIRECT_URL = "{{ route('sales-invoices.direct-print', $invoice->id) }}";
var STATUS_URL = "{{ url('print-jobs') }}";

function directPrint() {
  var copies  = Math.max(1, Math.min(10, parseInt(document.getElementById('copiesInput').value) || 1));
  var btn     = document.getElementById('btnBridge');
  btn.disabled    = true;
  btn.textContent = '⏳ Sending…';
  showStatus('Queuing print job…', 'info');
  tpLog('B11: Direct Print clicked — copies=' + copies + ' width=' + _mm + 'mm');

  fetch(DIRECT_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
      'Accept': 'application/json'
    },
    credentials: 'same-origin',
    body: JSON.stringify({ format: 'escpos', station: 'main', width: _mm, copies: copies })
  })
  .then(function (r) { return r.json(); })
  .then(function (d) {
    btn.disabled    = false;
    btn.innerHTML   = '&#9889; Direct Print';
    if (d.success) {
      showStatus('Job #' + d.job_id + ' queued — waiting for bridge to print…', 'info');
      tpLog('B12: Job #' + d.job_id + ' queued');
      pollJob(d.job_id, 0);
    } else {
      showStatus('✗ ' + (d.error || 'Enqueue failed'), 'err');
      tpLog('B12-ERROR: enqueue failed', d.error || '', true);
    }
  })
  .catch(function (e) {
    btn.disabled    = false;
    btn.innerHTML   = '&#9889; Direct Print';
    showStatus('✗ Network error: ' + e.message, 'err');
    tpLog('B12-ERROR: ' + e.message, null, true);
  });
}

function pollJob(id, n) {
  if (n > 15) {
    showStatus('✗ Job #' + id + ' timed out — is the bridge client running on the shop PC?', 'err');
    tpLog('B14: Job #' + id + ' timed out', null, true);
    return;
  }
  setTimeout(function () {
    fetch(STATUS_URL + '/' + id + '/status', {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d.status === 'done') {
        showStatus('✓ Printed successfully (job #' + id + ')', 'ok');
        tpLog('B13: Job #' + id + ' PRINTED by bridge');
      } else if (d.status === 'failed') {
        showStatus('✗ Print failed: ' + (d.error || 'bridge reported failure'), 'err');
        tpLog('B13-ERROR: Job #' + id + ' FAILED', d.error, true);
      } else {
        pollJob(id, n + 1);
      }
    })
    .catch(function () { pollJob(id, n + 1); });
  }, 2000);
}

/* ── RawBT one-tap (Android) — ESC/POS RASTER MODE ────────────────
   Renders the on-screen receipt to a canvas with html2canvas, then
   converts each pixel row into a 1-bit GS v 0 raster bitmap and sends
   the raw ESC/POS bytes via intent:base64,...

   WHY NOT rawbt:<data-url>:
     That scheme sends a PNG/JPEG the printer must buffer entirely in RAM
     before the print head moves. Long receipts overflow the buffer and
     the printer stops mid-page waiting for a FEED press.

   WHY GS v 0 RASTER:
     Bytes stream line-by-line to the print head — same as text mode.
     The printer never needs to hold the full image in memory, so there
     is no buffer-overflow regardless of receipt length.
   ──────────────────────────────────────────────────────────────────── */
@php
  $rb80 = $rawbtPayload80 ?? ($rawbtPayload ?? null);
  $rb58 = $rawbtPayload58 ?? ($rawbtPayload ?? null);
@endphp
var RAWBT_PAYLOADS = { 80: '{{ $rb80 ?? '' }}', 58: '{{ $rb58 ?? '' }}' };

function ensureHtml2Canvas(cb) {
  if (window.html2canvas) return cb(null);
  var s = document.createElement('script');
  s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
  s.onload  = function () { cb(null); };
  s.onerror = function () { cb(new Error('could not load html2canvas')); };
  document.head.appendChild(s);
}

function rawbtTextFallback(reason) {
  var p = RAWBT_PAYLOADS[_mm] || RAWBT_PAYLOADS[80];
  if (!p) { showStatus('✗ RawBT: no payload available', 'err'); return; }
  tpLog('B18: ESC/POS text fallback', reason, true);
  window.location.href = 'intent:base64,' + p + '#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;';
}

/* Convert html2canvas output → ESC/POS GS v 0 raster bytes */
function canvasToEscpos(canvas) {
  var ctx    = canvas.getContext('2d');
  var pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
  var W      = canvas.width;
  var H      = canvas.height;
  var bpr    = Math.ceil(W / 8);   // bytes per raster row

  /* 1-bit bitmap: luminance threshold < 128 → black dot */
  var bitmap = new Uint8Array(bpr * H);
  for (var y = 0; y < H; y++) {
    for (var bx = 0; bx < bpr; bx++) {
      var b = 0;
      for (var bit = 0; bit < 8; bit++) {
        var x = bx * 8 + bit;
        if (x < W) {
          var i    = (y * W + x) * 4;
          var luma = 0.299 * pixels[i] + 0.587 * pixels[i+1] + 0.114 * pixels[i+2];
          if (luma < 128) b |= (0x80 >> bit);
        }
      }
      bitmap[y * bpr + bx] = b;
    }
  }

  /* ESC/POS envelope:
       ESC @        — init printer
       ESC 3 0      — zero line spacing (rows butt up, no gap)
       GS v 0 mode xL xH yL yH  — raster image
       <bitmap>
       ESC 2        — restore default line spacing
       LF LF LF    — feed enough to expose receipt from slot
       GS V A 0    — full cut (feed + cut)                     */
  var xL = bpr & 0xFF, xH = (bpr >> 8) & 0xFF;
  var yL = H   & 0xFF, yH = (H   >> 8) & 0xFF;
  var hdr = [0x1B,0x40, 0x1B,0x33,0x00, 0x1D,0x76,0x30,0x00, xL,xH,yL,yH];
  var ftr = [0x1B,0x32, 0x0A,0x0A,0x0A,0x0A, 0x1D,0x56,0x41,0x00];

  var out = new Uint8Array(hdr.length + bitmap.length + ftr.length);
  out.set(hdr, 0);
  out.set(bitmap, hdr.length);
  out.set(ftr, hdr.length + bitmap.length);
  return out;
}

/* Safe base64 for large Uint8Arrays — avoids stack overflow from spread */
function uint8ToBase64(bytes) {
  var binary = '', chunk = 8192;
  for (var i = 0; i < bytes.length; i += chunk) {
    binary += String.fromCharCode.apply(null, bytes.subarray(i, Math.min(i + chunk, bytes.length)));
  }
  return btoa(binary);
}

function rawbtImagePrint() {
  tpLog('B16: RawBT raster print — rendering receipt (' + _mm + 'mm)');
  showStatus('📷 Capturing receipt…', 'info');

  ensureHtml2Canvas(function (loadErr) {
    if (loadErr) { rawbtTextFallback(loadErr.message); return; }

    var r   = document.getElementById('receipt');
    var cut = document.getElementById('cutLine');
    if (cut) cut.style.display = 'none';

    /* 80mm @ 203 DPI = 640 dots,  58mm = 464 dots */
    var targetDots = _mm === 58 ? 464 : 640;
    var scale      = targetDots / Math.max(1, r.offsetWidth);

    html2canvas(r, {
      backgroundColor: '#ffffff',
      scale:           scale,
      useCORS:         true,
      allowTaint:      true,
      logging:         false,
      imageTimeout:    8000
    }).then(function (canvas) {
      if (cut) cut.style.display = '';

      tpLog('B16a: canvas ' + canvas.width + 'x' + canvas.height + 'px — converting to GS v 0 raster');
      showStatus('🖨 Building ESC/POS bytes…', 'info');

      /* Yield to the UI thread before the heavy pixel loop */
      setTimeout(function () {
        try {
          var escBytes = canvasToEscpos(canvas);
          var b64      = uint8ToBase64(escBytes);

          tpLog('B17: raster ' + escBytes.length + ' bytes (' +
                Math.round(b64.length / 1024) + 'KB b64) — launching RawBT intent');
          showStatus('📱 Opening RawBT…', 'info');

          window.location.href = 'intent:base64,' + b64 +
            '#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;';

          setTimeout(function () {
            showStatus('📱 Tap <b>Bluetooth</b> to retry if RawBT did not open', 'info');
          }, 2500);
        } catch (e) {
          tpLog('B16-ERR: raster conversion failed: ' + e.message, null, true);
          rawbtTextFallback(e.message);
        }
      }, 50);

    }).catch(function (e) {
      if (cut) cut.style.display = '';
      tpLog('B16-ERR: html2canvas failed: ' + e.message, null, true);
      rawbtTextFallback(e.message);
    });
  });
}

(function () {
  var btn       = document.getElementById('btnRawbt');
  var tip       = document.getElementById('rawbtTip');
  var ua        = navigator.userAgent;
  var isAndroid = /android/i.test(ua);                          // Android phone or tablet
  var isMobile  = /iphone|ipad|ipod/i.test(ua);                // iOS (no RawBT, use browser print)

  if (isAndroid) {
    /* ── ANDROID / ANDROID TABLET ──────────────────────────────────
       Wire manual button + auto-trigger after page is fully painted. */
    if (btn) {
      btn.classList.remove('unavailable');
      btn.href = '#';
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        rawbtImagePrint();
      });
    }
    if (tip) tip.innerHTML = 'Prints receipt as image<br>via the RawBT app.';

    window.addEventListener('load', function () {
      tpLog('B16a: Android detected — auto-launching RawBT image capture');
      /* 900ms delay: let fonts, images and layout finish rendering first */
      setTimeout(rawbtImagePrint, 900);
    });

  } else if (isMobile) {
    /* ── iOS / IPAD ─────────────────────────────────────────────────
       RawBT is Android-only; fall back to browser print on iOS.     */
    if (tip) tip.innerHTML = 'Use 🖨 Browser Print on iOS.';
    tpLog('B6b: iOS detected — auto-opening browser print');
    window.addEventListener('load', function () { setTimeout(doPrint, 500); });

  } else {
    /* ── DESKTOP ─────────────────────────────────────────────────────
       Grey-out Bluetooth button; auto-open OS print dialog.          */
    if (tip) tip.innerHTML = 'Bluetooth (RawBT) is for<br><strong style="color:#93c5fd">Android</strong> only.<br>'
      + '<span style="color:#6b7280;font-size:9px">Install &ldquo;RawBT&rdquo; from Play Store.</span>';
    tpLog('B6b: Desktop detected — auto-opening browser print dialog');
    window.addEventListener('load', function () { setTimeout(doPrint, 400); });
  }
})();

/* ── apply responsive width on load and on orientation change ──
   remembers the cashier's last 80/58 choice on this device        */
var savedMm = 80;
try { savedMm = parseInt(localStorage.getItem('tp_paper_mm')) || 80; } catch (e) {}
setWidth(savedMm === 58 ? 58 : 80);
window.addEventListener('resize', function () { setWidth(_mm); });

/* ── hide cut-line on print + log print lifecycle ── */
window.addEventListener('beforeprint', function() {
  tpLog('B9: beforeprint fired — browser is preparing the print preview');
  document.getElementById('cutLine').style.display = 'none';
});
window.addEventListener('afterprint', function() {
  tpLog('B10: afterprint fired — print dialog closed (printed OR cancelled)');
  document.getElementById('cutLine').style.display = '';
});

/* extra safety: some mobile browsers only support matchMedia, not before/afterprint */
if (window.matchMedia) {
  window.matchMedia('print').addListener(function(mql) {
    tpLog(mql.matches ? 'B9b: entering print mode (matchMedia)' : 'B10b: leaving print mode (matchMedia)');
  });
}
</script>
</body>
</html>