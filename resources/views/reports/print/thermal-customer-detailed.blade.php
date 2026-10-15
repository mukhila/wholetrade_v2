<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Thermal — Customer Detailed Report</title>
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

/* ── status bar ── */
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

/* ── RawBT tooltip ── */
.rawbt-tip { position: relative; display: inline-flex; }
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

/* ── printer body ── */
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
.printer-slot { background: #0f172a; height: 6px; border-radius: 3px; }
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
  background-image: repeating-linear-gradient(
    180deg, transparent 0px, transparent 2px,
    rgba(0,0,0,0.016) 2px, rgba(0,0,0,0.016) 3px
  );
  padding: 10px 9px 8px;
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
.mt2 { margin-top: 2px; }
.mt4 { margin-top: 4px; }
.mt6 { margin-top: 6px; }
.mt8 { margin-top: 8px; }

hr          { border: none; margin: 5px 0; }
hr.dash     { border-top: 1px dashed #000; }
hr.solid    { border-top: 1px solid  #000; }
hr.double   { border-top: 3px double #000; }

.store-name { text-align: center; font-size: 15px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
.store-sub  { text-align: center; font-size: 10px; line-height: 1.55; }
.rpt-banner { text-align: center; font-size: 11px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; margin: 3px 0; }

.kv { display: flex; justify-content: space-between; font-size: 10px; line-height: 1.55; padding: 1px 4px; }
.kv .k { font-weight: bold; white-space: nowrap; padding-right: 3px; }
.kv .v { text-align: right; font-size: 10px; }

/* Customer block */
.cust-hdr {
  background: #000;
  color: #fff;
  font-size: 10px;
  font-weight: bold;
  padding: 2px 4px;
  margin-top: 6px;
  letter-spacing: 0.5px;
}
.cust-meta { font-size: 9px; color: #444; padding: 1px 4px; }

/* Invoice row table */
.t-inv {
  width: 100%;
  border-collapse: collapse;
  font-size: 9px;
}
.t-inv thead th {
  font-size: 9px; font-weight: bold; padding: 1px 2px;
  border-bottom: 1px solid #000; text-align: left;
}
.t-inv thead th.r { text-align: right; }
.t-inv tbody td { padding: 1px 2px; font-size: 9px; vertical-align: top; }
.t-inv tbody td.r { text-align: right; }
.t-inv tfoot td { font-size: 9px; font-weight: bold; padding: 1px 2px; border-top: 1px solid #000; }
.t-inv tfoot td.r { text-align: right; }

.st-paid    { color: #166534; }
.st-partial { color: #92400e; }
.st-unpaid  { color: #dc2626; }

/* Totals */
.tot-row {
  display: flex; justify-content: space-between;
  font-size: 10px; line-height: 1.65; padding: 1px 4px;
}
.grand-row {
  display: flex; justify-content: space-between;
  font-size: 13px; font-weight: bold; line-height: 1.8; padding: 1px 4px;
}

/* Summary table */
.t-sum { width: 100%; border-collapse: collapse; font-size: 9px; }
.t-sum thead th { font-size: 9px; font-weight: bold; padding: 1px 2px; border-bottom: 1px solid #000; text-align: left; }
.t-sum thead th.r { text-align: right; }
.t-sum tbody td { padding: 1px 2px; font-size: 9px; }
.t-sum tbody td.r { text-align: right; }
.t-sum tfoot td { font-size: 9px; font-weight: bold; padding: 1px 2px; border-top: 1px solid #000; }
.t-sum tfoot td.r { text-align: right; }

.footer { text-align: center; font-size: 10px; line-height: 1.65; }
.footer .tagline { font-size: 11px; font-weight: bold; letter-spacing: 1px; margin: 2px 0; }

.cut-line { display: flex; align-items: center; gap: 4px; margin-top: 8px; font-size: 10px; color: #000; }
.cut-line::before, .cut-line::after { content: ''; flex: 1; border-top: 1px dashed #888; }
.cut-sym { font-size: 13px; color: #222; }

/* ============================================================
   PRINT
   ============================================================ */
@media print {
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  body  { background: none !important; padding: 0 !important; margin: 0 !important; display: block !important; }
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
    padding: 2px 4px 0 !important;
    box-shadow: none !important;
    page-break-after: always;
  }
  .receipt::after { display: none !important; }
  @page { margin: 4mm; size: 80mm auto; }
}

/* ============================================================
   DEBUG PANEL  (visible only with ?debug=1)
   ============================================================ */
#tpDebugPanel {
  position: fixed; left: 0; right: 0; bottom: 0;
  max-height: 45vh; overflow-y: auto;
  background: #000; color: #4ade80;
  font-family: 'Courier New', monospace; font-size: 10px; line-height: 1.5;
  padding: 6px 10px 10px; z-index: 99999;
  border-top: 2px solid #4ade80; display: none;
}
#tpDebugPanel .tp-err  { color: #f87171; font-weight: bold; }
#tpDebugPanel .tp-head { color: #fbbf24; font-weight: bold; }
@media print { #tpDebugPanel { display: none !important; } }
</style>

<script>
(function () {
  window.TP_DEBUG = /[?&]debug=1/.test(window.location.search);

  var LOG_URL = "{{ route('sales-invoices.thermal-print.client-log') }}";
  var CSRF    = "{{ csrf_token() }}";
  var REPORT  = 'CUSTOMER-REPORT';
  var seq     = 0;

  function panel() {
    var p = document.getElementById('tpDebugPanel');
    if (!p && document.body) {
      p = document.createElement('div');
      p.id = 'tpDebugPanel';
      p.innerHTML = '<div class="tp-head">THERMAL PRINT DEBUG — ' + REPORT + ' (remove ?debug=1 to hide)</div>';
      document.body.appendChild(p);
    }
    if (p && window.TP_DEBUG) p.style.display = 'block';
    return p;
  }

  window.tpLog = function (step, detail, isError) {
    seq++;
    var time = new Date().toTimeString().split(' ')[0];
    var line = '#' + seq + ' [' + time + '] ' + step + (detail ? ' | ' + detail : '');
    try { (isError ? console.error : console.log)('[THERMAL-PRINT] ' + line); } catch (e) {}
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
    try {
      fetch(LOG_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        credentials: 'same-origin',
        keepalive: true,
        body: JSON.stringify({ step: line, detail: detail || null, invoice: REPORT })
      });
    } catch (e) {}
  };

  window.tpLogLocal = function (msg) {
    try { console.warn('[THERMAL-PRINT] ' + msg); } catch (e) {}
  };

  window.onerror = function (msg, src, line, col) {
    window.tpLog('B-ERROR: JS error', msg + ' @ ' + (src || '?') + ':' + line + ':' + col, true);
  };

  window.tpLog('B1: HTML received, logger running', 'url=' + window.location.pathname + window.location.search);
  document.addEventListener('DOMContentLoaded', function () { window.tpLog('B2: DOM ready'); });
  window.addEventListener('load', function () { window.tpLog('B3: Page fully loaded — ready to print'); });
})();
</script>
</head>
<body>

@php
  $grouped    = collect($detailed)->groupBy('customer_name');
  $grandTotal = collect($detailed)->sum('net_total');
  $grandPaid  = collect($detailed)->sum('paid_amount');
  $grandBal   = collect($detailed)->sum('balance_due');
  $cs         = $companySettings;
@endphp

{{-- ── TOOLBAR ── --}}
<div class="toolbar" id="toolbar">

  <a class="btn btn-back" href="{{ url()->previous() }}">&#8592; Back</a>

  <div class="toolbar-sep"></div>

  <span class="tlabel">PAPER:</span>
  <button class="btn active" id="btn80" onclick="setWidth(80)">80 mm</button>
  <button class="btn"        id="btn58" onclick="setWidth(58)">58 mm</button>

  <div class="toolbar-sep"></div>

  <div class="copies-wrap">
    <span class="tlabel">COPIES:</span>
    <input type="number" id="copiesInput" class="copies-input" value="1" min="1" max="10">
  </div>

  <div class="toolbar-sep"></div>

  <button class="btn btn-direct" id="btnBridge" onclick="directPrint()">
    &#9889; Direct Print
  </button>

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

  <button class="btn btn-browser" onclick="doPrint()">
    &#128438; Browser Print
  </button>

</div>

{{-- status bar --}}
<div id="printStatusBar"></div>

{{-- ── PRINTER SHELL ── --}}
<div class="printer-body" id="printerBody">
  <div class="printer-slot"></div>
</div>

<div class="paper-frame" id="paperFrame">
<div class="receipt" id="receipt">

  {{-- ── STORE HEADER ── --}}
  <div class="store-name">{{ $cs->company_name ?? 'BUSINESS NAME' }}</div>
  <div class="store-sub mt2">
    {{ $cs->address ?? '' }}<br>
    @if($cs->phone_no ?? null)Ph: {{ $cs->phone_no }}@endif
    @if($cs->registration_no ?? null) | Reg: {{ $cs->registration_no }}@endif
  </div>

  <hr class="dash">

  <div class="rpt-banner">&#9632;&nbsp;CUSTOMER DETAILED REPORT&nbsp;&#9632;</div>
  <div class="tc xs mt2">{{ now()->format('d M Y, h:i A') }}</div>

  {{-- ── FILTERS ── --}}
  @if(!empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['payment_status']))
  <hr class="dash">
  @if(!empty($filters['date_from']) || !empty($filters['date_to']))
  <div class="kv">
    <span class="k">Period:</span>
    <span class="v">
      {{ !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') : '—' }}
      to
      {{ !empty($filters['date_to'])   ? \Carbon\Carbon::parse($filters['date_to'])->format('d M Y')   : '—' }}
    </span>
  </div>
  @endif
  @if(!empty($filters['payment_status']))
  <div class="kv">
    <span class="k">Status:</span>
    <span class="v">{{ ucfirst($filters['payment_status']) }}</span>
  </div>
  @endif
  @endif

  <hr class="dash">

  {{-- ── PER-CUSTOMER INVOICE LIST ── --}}
  @foreach($grouped as $customerName => $invoices)
  <div class="cust-hdr">{{ strtoupper($customerName) }}</div>
  <div class="cust-meta">
    {{ $invoices->count() }} invoice(s) &nbsp;|&nbsp;
    Total: {{ number_format($invoices->sum('net_total'), 2) }} &nbsp;|&nbsp;
    Bal: {{ number_format($invoices->sum('balance_due'), 2) }}
  </div>

  <table class="t-inv">
    <thead>
      <tr>
        <th style="width:30%;">Invoice</th>
        <th style="width:22%;">Date</th>
        <th class="r" style="width:24%;">Total</th>
        <th class="r" style="width:24%;">Balance</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoices as $inv)
      <tr>
        <td>{{ $inv['invoice_number'] }}</td>
        <td>{{ \Carbon\Carbon::parse($inv['invoice_date'])->format('d/m/y') }}</td>
        <td class="r">{{ number_format($inv['net_total'], 2) }}</td>
        <td class="r @if($inv['balance_due'] > 0) st-unpaid @else st-paid @endif">
          {{ number_format($inv['balance_due'], 2) }}
        </td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2" class="b">Subtotal</td>
        <td class="r">{{ number_format($invoices->sum('net_total'), 2) }}</td>
        <td class="r st-unpaid">{{ number_format($invoices->sum('balance_due'), 2) }}</td>
      </tr>
    </tfoot>
  </table>
  @endforeach

  <hr class="double">

  {{-- ── GRAND TOTAL ── --}}
  <div class="tot-row"><span>Total Invoices</span><span>{{ collect($detailed)->count() }} ({{ $grouped->count() }} customers)</span></div>
  <div class="tot-row"><span>Total Paid</span><span>{{ number_format($grandPaid, 2) }}</span></div>
  <div class="grand-row"><span>Total</span><span>{{ number_format($grandTotal, 2) }}</span></div>
  <div class="tot-row b" style="color:#dc2626;"><span>Outstanding Balance</span><span>{{ number_format($grandBal, 2) }}</span></div>

  <hr class="dash">

  {{-- ── CUSTOMER SUMMARY ── --}}
  <div class="rpt-banner" style="font-size:9px;letter-spacing:1px;">CUSTOMER SUMMARY</div>
  <table class="t-sum mt2">
    <thead>
      <tr>
        <th>Customer</th>
        <th class="r">Sales</th>
        <th class="r">Balance</th>
      </tr>
    </thead>
    <tbody>
      @foreach($summary as $row)
      <tr>
        <td>{{ $row['customer_name'] }}</td>
        <td class="r">{{ number_format($row['total_sales'], 2) }}</td>
        <td class="r @if($row['total_balance'] > 0) st-unpaid @else st-paid @endif">
          {{ number_format($row['total_balance'], 2) }}
        </td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td class="b">TOTAL</td>
        <td class="r b">{{ number_format(collect($summary)->sum('total_sales'), 2) }}</td>
        <td class="r b st-unpaid">{{ number_format(collect($summary)->sum('total_balance'), 2) }}</td>
      </tr>
    </tfoot>
  </table>

  <hr class="dash">

  {{-- ── FOOTER ── --}}
  <div class="footer">
    <div class="tagline">&#10022; END OF REPORT &#10022;</div>
    <hr class="dash" style="margin:4px 0;">
    <div class="xs">Powered by <span class="b">Techgeeks</span></div>
  </div>

  {{-- ── CUT LINE ── --}}
  <div class="cut-line" id="cutLine">
    <span class="cut-sym">&#9988;</span>
    <span style="white-space:nowrap;font-size:9px;">CUT HERE</span>
  </div>

</div>{{-- /receipt --}}
</div>{{-- /paper-frame --}}

<script>
/* ── width toggle ── */
var _mm = 80;

function setWidth(mm) {
  _mm = mm;
  var idealPx = mm === 58 ? 248 : 320;
  var maxPx   = Math.floor(window.innerWidth - 24);
  var shellPx = Math.min(idealPx + 28, maxPx);
  var rcptPx  = Math.min(idealPx, maxPx - 12);
  document.getElementById('receipt').style.width     = rcptPx + 'px';
  document.getElementById('printerBody').style.width = shellPx + 'px';
  document.getElementById('paperFrame').style.width  = shellPx + 'px';
  document.getElementById('btn80').classList.toggle('active', mm === 80);
  document.getElementById('btn58').classList.toggle('active', mm === 58);
  try { localStorage.setItem('tp_paper_mm', String(mm)); } catch (e) {}
  tpLog('B4: Paper width changed', mm + 'mm');
}

/* ── status bar ── */
function showStatus(msg, type) {
  var bar = document.getElementById('printStatusBar');
  bar.textContent   = msg;
  bar.className     = type || 'info';
  bar.style.display = 'block';
  if (type === 'ok') setTimeout(function () { bar.style.display = 'none'; }, 5000);
}

/* ── browser print ── */
var _printClones = [];

function doPrint() {
  tpLog('B6: Browser Print clicked');
  var copies  = Math.max(1, Math.min(10, parseInt(document.getElementById('copiesInput').value, 10) || 1));
  var frame   = document.getElementById('paperFrame');
  var receipt = document.getElementById('receipt');
  _printClones = [];
  for (var i = 1; i < copies; i++) {
    var clone = receipt.cloneNode(true);
    clone.removeAttribute('id');
    frame.appendChild(clone);
    _printClones.push(clone);
  }
  try { window.print(); } catch (e) { tpLog('B6-ERR: ' + e.message, null, true); }
}

/* ── direct print (shop bridge) ── */
var DIRECT_URL = "{{ route('reports.customers.direct-print-thermal') }}";
var STATUS_URL = "{{ url('print-jobs') }}";
var REPORT_FILTERS = {!! \Illuminate\Support\Js::from($filters) !!};

function directPrint() {
  var copies = Math.max(1, Math.min(10, parseInt(document.getElementById('copiesInput').value) || 1));
  tpLog('B11: Direct Print clicked — copies=' + copies + ' width=' + _mm + 'mm');

  var btn = document.getElementById('btnBridge');
  btn.disabled    = true;
  btn.textContent = '⏳ Sending…';
  showStatus('Queuing print job…', 'info');

  fetch(DIRECT_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
      'Accept': 'application/json'
    },
    credentials: 'same-origin',
    body: JSON.stringify({ format: 'escpos', station: 'main', width: _mm, copies: copies, filters: REPORT_FILTERS })
  })
  .then(function (r) {
    tpLog('B11c: server HTTP ' + r.status);
    return r.json();
  })
  .then(function (d) {
    btn.disabled    = false;
    btn.innerHTML   = '&#9889; Direct Print';
    if (d.success) {
      showStatus('Job #' + d.job_id + ' queued — waiting for bridge to print…', 'info');
      tpLog('B12: Job #' + d.job_id + ' queued');
      pollJob(d.job_id, 0);
    } else {
      showStatus('✗ ' + (d.error || 'Enqueue failed'), 'err');
      tpLog('B12-ERR: ' + (d.error || ''), null, true);
    }
  })
  .catch(function (e) {
    btn.disabled    = false;
    btn.innerHTML   = '&#9889; Direct Print';
    showStatus('✗ Network error: ' + e.message, 'err');
    tpLog('B12-NET-ERR: ' + e.message, null, true);
  });
}

function pollJob(id, n) {
  if (n > 15) {
    showStatus('✗ Job #' + id + ' timed out — is the Whole Trade Print Service running?', 'err');
    tpLog('B14-TIMEOUT: Job #' + id, null, true);
    return;
  }
  tpLog('B14-POLL: Job #' + id + ' poll #' + (n + 1) + ' (~' + (n * 2) + 's)');
  setTimeout(function () {
    fetch(STATUS_URL + '/' + id + '/status', {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      tpLog('B14-POLL: Job #' + id + ' status=' + d.status);
      if (d.status === 'done') {
        showStatus('✓ Printed successfully (job #' + id + ')', 'ok');
        tpLog('B13: Job #' + id + ' DONE');
      } else if (d.status === 'failed') {
        showStatus('✗ Print failed: ' + (d.error || 'bridge error'), 'err');
        tpLog('B13-ERR: Job #' + id + ' FAILED', d.error, true);
      } else {
        pollJob(id, n + 1);
      }
    })
    .catch(function (e) {
      tpLog('B14-ERR: ' + e.message + ' — retrying', null, true);
      pollJob(id, n + 1);
    });
  }, 2000);
}

/* ── RawBT (Bluetooth) ── */
@php
  $rb80 = $rawbtPayload80 ?? '';
  $rb58 = $rawbtPayload58 ?? '';
@endphp
var RAWBT_PAYLOADS = { 80: '{{ $rb80 }}', 58: '{{ $rb58 }}' };

var _rawbtBusy      = false;
var _countdownTimer = null;

function rawbtPrint() {
  if (_rawbtBusy) { tpLog('B16-SKIP: rawbtPrint called while busy'); return; }

  var p = RAWBT_PAYLOADS[_mm] || RAWBT_PAYLOADS[80];
  if (!p) {
    showStatus('✗ ESC/POS payload missing — check server logs', 'err');
    tpLog('B16-ERR: no payload for ' + _mm + 'mm', null, true);
    return;
  }

  _rawbtBusy = true;
  tpLog('B16: RawBT print — ' + _mm + 'mm payload ~' + Math.round(p.length * 0.75 / 1024) + 'KB');
  showStatus('📱 Opening RawBT…', 'info');

  var sep = '#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;S.ts=' + Date.now() + ';end;';
  window.location.href = 'intent:base64,' + p + sep;

  var bytes  = Math.round(p.length * 0.75);
  var lockMs = Math.min(20000, Math.max(8000, 4000 + Math.round(bytes / 40) * 60));
  tpLog('B16b: busy lock ' + lockMs + 'ms');
  startBtCountdown(lockMs);
}

function startBtCountdown(lockMs) {
  var btn       = document.getElementById('btnRawbt');
  var remaining = Math.ceil(lockMs / 1000);

  if (btn) { btn.classList.add('unavailable'); btn.style.pointerEvents = 'none'; btn.style.opacity = '0.5'; }
  if (_countdownTimer) clearInterval(_countdownTimer);

  function tick() {
    if (remaining <= 0) {
      clearInterval(_countdownTimer);
      _countdownTimer = null;
      _rawbtBusy = false;
      if (btn) { btn.classList.remove('unavailable'); btn.style.pointerEvents = ''; btn.style.opacity = ''; }
      showStatus('✅ Printer ready — safe to print next copy', 'ok');
      tpLog('B16c: busy lock released');
      return;
    }
    showStatus('🖨️ Printing… wait ' + remaining + 's', 'info');
    remaining--;
  }

  tick();
  _countdownTimer = setInterval(tick, 1000);
}

/* ── device detection ── */
(function () {
  var btn       = document.getElementById('btnRawbt');
  var tip       = document.getElementById('rawbtTip');
  var ua        = navigator.userAgent;
  var isAndroid = /android/i.test(ua);
  var isMobile  = /iphone|ipad|ipod/i.test(ua);

  if (isAndroid) {
    if (btn) {
      btn.classList.remove('unavailable');
      btn.href = '#';
      btn.addEventListener('click', function (e) { e.preventDefault(); rawbtPrint(); });
    }
    if (tip) tip.innerHTML = 'Sends ESC/POS text to RawBT.<br>Report prints instantly.';
    tpLog('B6b: Android detected');

  } else if (isMobile) {
    if (tip) tip.innerHTML = 'Use 🖨 Browser Print on iOS.';
    tpLog('B6b: iOS detected — auto-opening browser print');
    window.addEventListener('load', function () { setTimeout(doPrint, 500); });

  } else {
    if (tip) tip.innerHTML = 'Bluetooth (RawBT) is for<br><strong style="color:#93c5fd">Android</strong> only.<br>'
      + '<span style="color:#6b7280;font-size:9px">Install &ldquo;RawBT&rdquo; from Play Store.</span>';
    tpLog('B6b: Desktop detected — auto-opening browser print dialog');
    window.addEventListener('load', function () { setTimeout(doPrint, 400); });
  }
})();

/* ── localStorage width ── */
var savedMm = 80;
try { savedMm = parseInt(localStorage.getItem('tp_paper_mm')) || 80; } catch (e) {}
setWidth(savedMm === 58 ? 58 : 80);
window.addEventListener('resize', function () { setWidth(_mm); });

/* ── print lifecycle ── */
window.addEventListener('beforeprint', function () {
  tpLog('B9: beforeprint fired');
  document.getElementById('cutLine').style.display = 'none';
});
window.addEventListener('afterprint', function () {
  tpLog('B10: afterprint fired');
  document.getElementById('cutLine').style.display = '';
  _printClones.forEach(function (c) { if (c.parentNode) c.parentNode.removeChild(c); });
  _printClones = [];
});
</script>
</body>
</html>
