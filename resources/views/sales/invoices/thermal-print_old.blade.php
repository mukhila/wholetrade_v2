<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thermal Receipt — {{ $invoice->invoice_number }}</title>
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
</style>
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
.btn-print  { background: #065f46; border-color: #059669; color: #d1fae5; font-weight: bold; padding: 6px 20px; }
.btn-print:hover { background: #047857; }
.btn-back   { background: #1f2937; border-color: #6b7280; color: #9ca3af; }
.copies-input {
  width: 44px;
  padding: 5px 6px;
  border: 1px solid #374151;
  background: #1f2937;
  color: #f3f4f6;
  border-radius: 3px;
  font-size: 12px;
  text-align: center;
  outline: none;
  font-family: 'Noto Sans', Arial, sans-serif;
}

/* printer body */
.printer-body {
  background: #1f2937;
  border-radius: 8px 8px 0 0;
  padding: 12px 14px 0;
  box-shadow: 0 2px 0 #374151;
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
}

/* ============================================================
   RECEIPT PAPER
   ============================================================ */
.receipt {
  width: 302px;
  background: #fefefe;
  background-image:
    repeating-linear-gradient(
      180deg,
      transparent 0px,
      transparent 2px,
      rgba(0,0,0,0.016) 2px,
      rgba(0,0,0,0.016) 3px
    );
  padding: 10px 9px 8px;
  font-family: 'Noto Sans', Arial, sans-serif;
  font-size: 11px;
  font-weight: bold;
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
  padding:1px 15px;
}
.kv .k { font-weight: bold; white-space: nowrap; padding-right: 3px; }
.kv .v { text-align: right; font-size: 10px; }

/* ============================================================
   ITEMS TABLE
   ============================================================ */
.t-items {
  width: 95%;
  border-collapse: collapse;
  font-size: 10px;
  margin:0 auto;
}
.t-items colgroup col:nth-child(1) { width: 52%; }
.t-items colgroup col:nth-child(2) { width: 13%; }
.t-items colgroup col:nth-child(3) { width: 18%; }
.t-items colgroup col:nth-child(4) { width: 17%; }

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

.item-code { font-weight: bold; font-size: 10px; padding: 2px 0 0; }
.item-desc { font-size: 10px; color: #000; padding: 0 0 1px 3px; }
.item-vals { font-size: 10px; padding: 0 0 3px; margin:10px 0px;}

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
  padding:1px 15px;
}
.tot-row.muted { color: #000; }
.grand-row {
  display: flex;
  justify-content: space-between;
  font-size: 15px;
  font-weight: bold;
  line-height: 1.8;
  padding:1px 15px;
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
.pay-section { font-size: 10px; line-height: 1.6;padding:1px 15px; }
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

  body  {
    background: none !important;
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
  }
  .toolbar       { display: none !important; }
  .printer-body  { display: none !important; }
  .printer-slot  { display: none !important; }
  .paper-frame   { all: unset !important; display: block !important; }
  .cut-line      { display: none !important; }

  .receipt {
    background: #fff !important;
    background-image: none !important;
    padding: 2px 4px 0 !important;
    box-shadow: none !important;
    page-break-after: always;
  }
  .receipt::after { display: none !important; }

  @page { margin: 0; size: 80mm auto; }
}
</style>

{{-- QZ Tray client library (talks to the QZ Tray app on the cashier PC) --}}
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
{{-- html2canvas: renders the receipt to a PNG in the browser (no URLs for QZ to resolve) --}}
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
</head>
<body>

{{-- ── TOOLBAR ── --}}
<div class="toolbar" id="toolbar">
  <span class="tlabel">WIDTH:</span>
  <button class="btn active" id="btn80" onclick="setWidth(80)">80 mm</button>
  <button class="btn"        id="btn58" onclick="setWidth(58)">58 mm</button>
  &nbsp;
  <span class="tlabel">COPIES:</span>
  <input type="number" id="copiesInput" class="copies-input" value="1" min="1" max="10">

  <button class="btn btn-print" onclick="doPrint()">&#128438; Print / Save PDF</button>

  &nbsp;|&nbsp;
  <span class="tlabel">
    <span id="qzDot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-right:5px;vertical-align:middle;"></span>
    <span id="qzText">QZ Disconnected</span>
  </span>
  <select id="qzPrinter" class="copies-input" style="width:auto;min-width:130px;text-align:left;"></select>
  <button class="btn" title="Refresh printer list" onclick="qzLoadPrinters()">&#8635;</button>
  <button class="btn btn-print" onclick="qzPrint()">&#128424;&#65039; Print (QZ Tray)</button>

  &nbsp;|&nbsp;
  <span class="tlabel">
    <span id="btDot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-right:5px;vertical-align:middle;"></span>
    <span id="btText">BT: Off</span>
  </span>
  <button class="btn" id="btConnectBtn" onclick="btConnect()">&#128268; Connect BT</button>
  <button class="btn btn-print" id="btPrintBtn" onclick="btPrint()" disabled style="opacity:.5;">&#128424; Print (BT)</button>

  <a class="btn btn-back" href="{{ url()->previous() }}">&#8592; Back</a>
</div>

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
  @if($invoice->due_date)
  <div class="kv">
    <span class="k">Due Date    :</span>
    <span class="v">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</span>
  </div>
  @endif
  <div class="kv">
    <span class="k">Customer    :</span>
    <span class="v">{{ $invoice->customer->name ?? 'WALK-IN' }}</span>
  </div>
  <div class="kv">
    <span class="k">Address    :</span>
    <span class="v">{{ $invoice->customer->address_line_1 ?? '' }}</span>
    <span class="v">{{ $invoice->customer->address_line_2 ?? '' }}</span>
  </div>
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
	  <colgroup><col><col><col><col></colgroup>
    <tbody>
      
      @foreach($invoice->items as $idx => $item)
        @php
          $sku      = $item->product->sku ?? '';
          $desc     = $item->item_description ?: ($item->product->name ?? '');
          $label    = $desc;
          $uomName  = $item->product->uom->name ?? '';
          $boxNote  = $item->box_qty > 0 ? $item->box_qty . ' box' : '';
          $subNote  = implode(' | ', array_filter([$uomName, $boxNote]));
          $qty      = $item->total_qty;
          $qtyFmt   = ($qty == floor($qty)) ? (int)$qty : number_format($qty, 2);
          $hasDisc  = $item->discount_amount > 0;
          $hasTax   = $item->tax_amount > 0;
        @endphp

        @if($idx > 0)
        <tr class="item-sep"><td colspan="4"></td></tr>
        @endif

        <tr>
          
        </tr>

        @if($subNote)
        <tr>
          <td colspan="4" class="item-desc">{{ $subNote }}</td>
        </tr>
        @endif

        <tr class="item-vals">
          <td class="" class="item-code">{{ $label }}</td>
          <td class="r">{{ $qtyFmt }}</td>
          <td class="r">{{ number_format($item->unit_price, 2) }}</td>
          <td class="r">{{ number_format($item->total_price, 2) }}</td>
        </tr>

        
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

  {{-- ── QR CODE ── --}}
  <hr class="dash">
  <div style="text-align:center; padding: 4px 0 2px;">
    <div style="font-size:10px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; margin-bottom:4px;">
      {{ strtoupper($cs->company_name ?? '') }}
    </div>
    <img src="{{ asset('assets/images/sgtrade_printqr.jpeg') }}"
         alt="Scan to Pay QR"
         style="width:130px; height:130px; object-fit:contain; display:block; margin:0 auto;">
    <div style="font-size:10px; font-weight:bold; letter-spacing:2px; text-transform:uppercase; margin-top:4px;">
      &#9632; SCAN TO PAY &#9632;
    </div>
    @if($cs->uen_no ?? null)
    <div style="font-size:10px; margin-top:2px;">PayNow UEN: {{ $cs->uen_no }}</div>
    @endif
  </div>

  <hr class="dash">
  <br>
  <br>
  <br>
  <br>
  <br>

  {{-- ── SIGNATURES ── --}}
  <div class="sig-wrap">
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-lbl">Authorized Signature</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-lbl">Customer Sign<br>&amp; Co. Stamp</div>
    </div>
  </div>

  <hr class="dash" style="margin-top:8px;">

  {{-- ── BARCODE ── --}}
  <!--div class="barcode-wrap">
    <div class="barcode" id="barcode"></div>
    <div class="barcode-num">{{ $invoice->invoice_number }}</div>
  </div-->

  <hr class="dash">

  {{-- ── FOOTER ── --}}
  <div class="footer">
    <div class="tagline">&#10022; THANK YOU FOR THE BUSINESS &#10022;</div>
    
    <hr class="dash" style="margin:4px 0;">
    <!--div class="xs b">Return Policy</div>
    <div class="xs">Returns within 7 days with original receipt.</div>
    <div class="xs">Items must be unused &amp; in original packaging.</div-->
    <hr class="dash" style="margin:4px 0;">
    <div class="xs">Powered by <span class="b">Techgeeks</span></div>
  </div>

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
  var px  = mm === 58 ? 226 : 302;
  var r   = document.getElementById('receipt');
  var pb  = document.getElementById('printerBody');
  var pf  = document.getElementById('paperFrame');
  r.style.width  = px + 'px';
  pb.style.width = (px + 28) + 'px';
  pf.style.width = (px + 28) + 'px';
  document.getElementById('btn80').classList.toggle('active', mm === 80);
  document.getElementById('btn58').classList.toggle('active', mm === 58);
}

/* ── CSS barcode ──
   Encodes each char into alternating black bars / white gaps.
   Width of each element driven by bit-fields of the char code.
─────────────────────────────────────────────────────────────── */
(function buildBarcode() {
  var code = {{ Js::from($invoice->invoice_number) }};
  var el   = document.getElementById('barcode');
  if (!el) return;

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
}());

/* ── multi-copy print ── */
var _printClones = [];

function doPrint() {
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

  window.print();
}

/* ── hide cut-line on print ── */
window.addEventListener('beforeprint', function() {
  document.getElementById('cutLine').style.display = 'none';
});
window.addEventListener('afterprint', function() {
  document.getElementById('cutLine').style.display = '';
  _printClones.forEach(function(c) { if (c.parentNode) c.parentNode.removeChild(c); });
  _printClones = [];
});
</script>

<script>
/* ============================================================
   QZ TRAY  —  print THIS receipt to a local 80mm thermal printer
   ------------------------------------------------------------
   We send the existing styled HTML to QZ Tray, which rasterizes
   it to the local printer (cPanel server never touches the
   printer). The on-screen design prints exactly as previewed.
   ============================================================ */

/* --- Security ---
   Leave both URLs EMPTY  => dev mode (unsigned, QZ shows the "Allow" prompt).
   Fill BOTH URLs         => silent printing, no prompt (see setup notes).      */
var QZ_SIGNING = {
  certUrl: '{{ url("/qz/certificate") }}',
  signUrl: '{{ url("/qz/sign") }}',
  csrf: '{{ csrf_token() }}'
};

qz.security.setCertificatePromise(function (resolve, reject) {
  if (!QZ_SIGNING.certUrl) { resolve(); return; }          // unsigned fallback
  fetch(QZ_SIGNING.certUrl)
    .then(function (r) { return r.text(); })
    .then(resolve)
    .catch(function () { resolve(); });
});

qz.security.setSignatureAlgorithm('SHA512');               // must match the server
qz.security.setSignaturePromise(function (toSign) {
  return function (resolve, reject) {
    if (!QZ_SIGNING.signUrl) { resolve(); return; }        // unsigned fallback
    fetch(QZ_SIGNING.signUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': QZ_SIGNING.csrf
      },
      body: 'request=' + encodeURIComponent(toSign)
    })
    .then(function (r) { return r.text(); })
    .then(resolve)
    .catch(function () { resolve(); });
  };
});

function qzSetStatus(ok) {
  var d = document.getElementById('qzDot');
  var t = document.getElementById('qzText');
  if (!d) return;
  d.style.background = ok ? '#10b981' : '#ef4444';
  t.textContent = ok ? 'QZ Connected' : 'QZ Disconnected';
}

async function qzConnect() {
  try {
    if (!qz.websocket.isActive()) {
      await qz.websocket.connect();   // wss://localhost:8181
    }
    qzSetStatus(true);
    await qzLoadPrinters();
    return true;
  } catch (e) {
    qzSetStatus(false);
    console.warn('QZ connect error:', e);
    return false;
  }
}

async function qzLoadPrinters() {
  try {
    if (!qz.websocket.isActive()) {
      var ok = await qzConnect();
      if (!ok) { alert('QZ Tray is not running. Please start the QZ Tray app.'); return; }
    }
    var list = await qz.printers.find();
    list = Array.isArray(list) ? list : [list];

    var sel = document.getElementById('qzPrinter');
    if (!sel) return;
    sel.innerHTML = '';

    var def = '';
    try { def = await qz.printers.getDefault(); } catch (e) {}

    list.forEach(function (name) {
      var o = document.createElement('option');
      o.value = name;
      o.textContent = name;
      if (name === def) o.selected = true;
      sel.appendChild(o);
    });
  } catch (e) {
    console.warn('qzLoadPrinters error:', e);
  }
}

/* Capture the on-screen receipt as a PNG (base64) using html2canvas,
   sized to the printer's dot width so the raster fills the paper.
   QZ converts this image to ESC/POS raster (raw), so there is no HTML
   to parse and no pixel/driver pipeline -> no "Malformed URL" and no
   "Cannot parse (FILE)" from mixing image + raw. The real on-screen
   fonts and the CSS barcode are preserved exactly. */
async function qzCaptureReceiptBase64(targetDots) {
  var el = document.getElementById('receipt');
  var cssW = el.getBoundingClientRect().width || 302;
  var scale = targetDots / cssW;   // make the captured image ~targetDots wide

  var canvas = await html2canvas(el, {
    scale: scale,
    backgroundColor: '#ffffff',
    useCORS: true,
    onclone: function (doc) {
      // clean up the OFF-SCREEN clone only (live preview untouched)
      var r = doc.getElementById('receipt');
      if (r) {
        r.style.backgroundImage = 'none';
        r.style.boxShadow = 'none';
        r.style.padding = '2px 4px 0';
      }
      var cut = doc.getElementById('cutLine');
      if (cut) cut.style.display = 'none';
    }
  });

  // strip the "data:image/png;base64," prefix -> raw base64 for QZ
  return canvas.toDataURL('image/png').split(',')[1];
}

async function qzPrint() {
  var ok = await qzConnect();
  if (!ok) { alert('Could not reach QZ Tray. Is the app running?'); return; }

  var sel = document.getElementById('qzPrinter');
  var printer = (sel && sel.value) ? sel.value : await qz.printers.getDefault();
  if (!printer) { alert('No printer selected.'); return; }

  // copies + paper width come from the existing toolbar controls
  var copies  = Math.max(1, Math.min(10, parseInt(document.getElementById('copiesInput').value, 10) || 1));
  var widthPx = parseInt(document.getElementById('receipt').style.width, 10) || 302;
  var widthMM = widthPx <= 226 ? 58 : 80;
  // Printable dots: 80mm = 576, 58mm = 384 (drop 576 to 512 if your roll wraps)
  var targetDots = (widthMM === 58) ? 384 : 576;

  // Plain RAW config (no pixel/size options needed - QZ rasters the image itself)
  var config = qz.configs.create(printer, { encoding: 'UTF-8' });

  var imgB64;
  try {
    imgB64 = await qzCaptureReceiptBase64(targetDots);
  } catch (e) {
    alert('Could not capture receipt image: ' + e);
    console.error(e);
    return;
  }

  // ESC/POS full cut after each copy: feed 4 lines, then GS V 0.
  // Use "\x1D\x56\x01" for a partial cut instead.
  var CUT = '\x0A\x0A\x0A\x0A\x1D\x56\x00';

  // EVERYTHING is type:'raw' -> single raw pipeline, correct order, native cut.
  var data = [];
  for (var i = 0; i < copies; i++) {
    data.push({
      type: 'raw',
      format: 'image',
      flavor: 'base64',
      data: imgB64,
      options: { language: 'ESCPOS', dotDensity: 'double' }   // image -> ESC/POS raster
    });
    data.push({ type: 'raw', format: 'command', flavor: 'plain', data: CUT });  // cut THIS copy
  }

  try {
    await qz.print(config, data);
  } catch (e) {
    alert('QZ print failed: ' + e);
    console.error(e);
  }
}

/* Auto-connect on load (silent if QZ Tray isn't installed yet) */
document.addEventListener('DOMContentLoaded', function () {
  qzConnect().catch(function () {});
});
</script>

<script>
/* ============================================================
   WEB BLUETOOTH  —  direct ESC/POS raster print to a BT
   thermal printer (no driver / no QZ Tray required).
   Works in Chrome 56+ (desktop & Android). Needs HTTPS or localhost.
   ============================================================ */

var _btDevice = null;
var _btChar   = null;

/* Known service/characteristic pairs for common BT thermal printers */
var BT_PROFILES = [
  /* Most common – generic Chinese 80mm/58mm BT printers */
  { svc: '000018f0-0000-1000-8000-00805f9b34fb', chr: '00002af1-0000-1000-8000-00805f9b34fb' },
  /* Peripage / Paperang style */
  { svc: '49535343-fe7d-4ae5-8fa9-9fafd205e455', chr: '49535343-8841-43f4-a8d4-ecbe34729bb3' },
  /* Some RP-series & other models */
  { svc: '0000ff00-0000-1000-8000-00805f9b34fb', chr: '0000ff02-0000-1000-8000-00805f9b34fb' },
  /* ZJ-5802 / ZJ-5805 */
  { svc: 'e7810a71-73ae-499d-8c15-faa9aef0c3f2', chr: 'bef8d6c9-9c21-4c9e-b632-bd58c1009f9f' },
];

function btSetStatus(connected, label) {
  var dot = document.getElementById('btDot');
  var txt = document.getElementById('btText');
  var btn = document.getElementById('btPrintBtn');
  if (dot) dot.style.background = connected ? '#10b981' : '#ef4444';
  if (txt) txt.textContent = connected ? ('BT: ' + (label || 'Connected')) : 'BT: Off';
  if (btn) { btn.disabled = !connected; btn.style.opacity = connected ? '1' : '.5'; }
}

async function btConnect() {
  if (!navigator.bluetooth) {
    alert('Web Bluetooth is not supported.\nUse Chrome on desktop or Android Chrome.\nHTTPS or localhost is required.');
    return;
  }
  try {
    _btDevice = await navigator.bluetooth.requestDevice({
      acceptAllDevices: true,
      optionalServices: BT_PROFILES.map(function (p) { return p.svc; })
    });

    _btDevice.addEventListener('gattserverdisconnected', function () {
      _btChar = null;
      btSetStatus(false);
    });

    var server = await _btDevice.gatt.connect();

    for (var i = 0; i < BT_PROFILES.length; i++) {
      try {
        var svc = await server.getPrimaryService(BT_PROFILES[i].svc);
        _btChar = await svc.getCharacteristic(BT_PROFILES[i].chr);
        btSetStatus(true, _btDevice.name || 'Printer');
        return;
      } catch (e) { /* try next profile */ }
    }

    alert('Connected to "' + (_btDevice.name || 'device') + '" but no recognised print service found.\nCheck that the printer is an ESC/POS model.');
    _btChar = null;
    btSetStatus(false);
  } catch (e) {
    if (e.name !== 'NotFoundError') {
      alert('Bluetooth error: ' + e.message);
    }
    btSetStatus(false);
  }
}

/* Convert a canvas element to a 1-bit ESC/POS GS v 0 raster payload */
function canvasToEscpos(canvas, dotsWide) {
  var ctx  = canvas.getContext('2d');
  var imgW = canvas.width;
  var imgH = canvas.height;
  var px   = ctx.getImageData(0, 0, imgW, imgH).data;

  var bytesPerLine = Math.ceil(dotsWide / 8);
  var raster = [];

  for (var y = 0; y < imgH; y++) {
    var line = new Uint8Array(bytesPerLine);
    for (var x = 0; x < dotsWide && x < imgW; x++) {
      var idx = (y * imgW + x) * 4;
      var lum = 0.299 * px[idx] + 0.587 * px[idx + 1] + 0.114 * px[idx + 2];
      if (lum < 128) line[x >> 3] |= (0x80 >> (x & 7)); // dark pixel = print
    }
    raster.push(line);
  }

  var height = raster.length;
  var out    = [];

  /* ESC @ — initialise */
  out.push(0x1B, 0x40);

  /* GS v 0 — raster bitmap (m=0 normal density) */
  out.push(0x1D, 0x76, 0x30, 0x00,
    bytesPerLine & 0xFF, (bytesPerLine >> 8) & 0xFF,
    height & 0xFF,       (height >> 8) & 0xFF);

  for (var r = 0; r < raster.length; r++) {
    for (var b = 0; b < raster[r].length; b++) out.push(raster[r][b]);
  }

  /* Feed + full cut */
  out.push(0x0A, 0x0A, 0x0A, 0x0A, 0x1D, 0x56, 0x00);

  return new Uint8Array(out);
}

/* Write data in BLE-safe chunks (max 512 bytes per writeValue) */
async function btWriteChunked(characteristic, data, chunkSize) {
  chunkSize = chunkSize || 512;
  for (var i = 0; i < data.length; i += chunkSize) {
    await characteristic.writeValue(data.slice(i, i + chunkSize));
  }
}

async function btPrint() {
  if (!_btChar) {
    alert('No Bluetooth printer connected.\nClick "Connect BT" first.');
    return;
  }

  var widthPx  = parseInt(document.getElementById('receipt').style.width, 10) || 302;
  var dotsWide = widthPx <= 226 ? 384 : 576;
  var scale    = dotsWide / (document.getElementById('receipt').getBoundingClientRect().width || 302);

  var canvas;
  try {
    canvas = await html2canvas(document.getElementById('receipt'), {
      scale: scale,
      backgroundColor: '#ffffff',
      useCORS: true,
      onclone: function (doc) {
        var r = doc.getElementById('receipt');
        if (r) { r.style.backgroundImage = 'none'; r.style.boxShadow = 'none'; r.style.padding = '2px 4px 0'; }
        var cut = doc.getElementById('cutLine');
        if (cut) cut.style.display = 'none';
      }
    });
  } catch (e) {
    alert('Could not render receipt: ' + e.message);
    return;
  }

  var copies  = Math.max(1, Math.min(10, parseInt(document.getElementById('copiesInput').value, 10) || 1));
  var payload = canvasToEscpos(canvas, dotsWide);

  var btn = document.getElementById('btPrintBtn');
  if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

  try {
    for (var i = 0; i < copies; i++) {
      await btWriteChunked(_btChar, payload);
    }
  } catch (e) {
    alert('Bluetooth print failed: ' + e.message);
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = '🖨 Print (BT)'; }
  }
}
</script>
</body>
</html>
