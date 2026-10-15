<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  font-family: 'Courier New', Courier, monospace;
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
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
}
.btn {
  font-family: 'Courier New', Courier, monospace;
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
  font-family: 'Courier New', Courier, monospace;
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
.xs  { font-size: 9px; line-height: 1.5; }
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
  font-size: 9px;
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
.kv .v { text-align: right; font-size: 9px; }

/* ============================================================
   ITEMS TABLE
   ============================================================ */
.t-items {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
}
.t-items colgroup col:nth-child(1) { width: 52%; }
.t-items colgroup col:nth-child(2) { width: 13%; }
.t-items colgroup col:nth-child(3) { width: 18%; }
.t-items colgroup col:nth-child(4) { width: 17%; }

.t-items thead th {
  font-size: 9px;
  font-weight: bold;
  text-align: left;
  padding: 1px 0;
}
.t-items thead th.r { text-align: right; }

.t-items tbody td { padding: 0; vertical-align: top; }
.t-items tbody td.r { text-align: right; }
.t-items tbody td.c { text-align: center; }

.item-code { font-weight: bold; font-size: 10px; padding: 2px 0 0; }
.item-desc { font-size: 9px; color: #333; padding: 0 0 1px 3px; }
.item-vals { font-size: 10px; padding: 0 0 3px; }

.item-sep td {
  border-top: 1px dotted #ccc;
  font-size: 1px;
  line-height: 1px;
  height: 1px;
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
.tot-row.muted { color: #555; }
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
  font-size: 9px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 2px;
}
.t-gst {
  width: 100%;
  border-collapse: collapse;
  font-size: 9px;
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
.pay-section { font-size: 10px; line-height: 1.6; }
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
.sig-lbl  { font-size: 9px; line-height: 1.4; }

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
  font-size: 8px;
  letter-spacing: 4px;
  text-align: center;
  margin-top: 2px;
  font-weight: bold;
}

/* ============================================================
   FOOTER
   ============================================================ */
.footer { text-align: center; font-size: 9px; line-height: 1.65; }
.footer .tagline { font-size: 10px; font-weight: bold; letter-spacing: 1px; margin: 2px 0; }

/* ============================================================
   CUT LINE
   ============================================================ */
.cut-line {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  font-size: 9px;
  color: #555;
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
  font-size: 9px;
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
</head>
<body>

{{-- ── TOOLBAR ── --}}
<div class="toolbar" id="toolbar">
  <span class="tlabel">WIDTH:</span>
  <button class="btn active" id="btn80" onclick="setWidth(80)">80 mm</button>
  <button class="btn"        id="btn58" onclick="setWidth(58)">58 mm</button>
  &nbsp;
  <button class="btn btn-print" onclick="window.print()">&#128438; Print / Save PDF</button>
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
  <div class="inv-banner">&#9632;&nbsp;TAX INVOICE&nbsp;&#9632;</div>

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
  <!--div class="kv mt2">
    <span class="k">Status      :</span>
    <span class="v"><span class="status-badge">{{ strtoupper($invoice->payment_status ?? $invoice->status) }}</span></span>
  </div-->

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
          <td colspan="4" class="item-code">{{ $label }}</td>
        </tr>

        @if($subNote)
        <tr>
          <td colspan="4" class="item-desc">{{ $subNote }}</td>
        </tr>
        @endif

        <tr class="item-vals">
          <td></td>
          <td class="r">{{ $qtyFmt }}</td>
          <td class="r">{{ number_format($item->unit_price, 2) }}</td>
          <td class="r">{{ number_format($item->total_price, 2) }}</td>
        </tr>

        @if($hasDisc)
        <tr>
          <td colspan="3" class="item-desc">Discount</td>
          <td class="r xs">-{{ number_format($item->discount_amount, 2) }}</td>
        </tr>
        @endif

        @if($hasTax)
        <tr>
          <td colspan="3" class="item-desc">Tax ({{ $item->tax_rate }}%)</td>
          <td class="r xs">{{ number_format($item->tax_amount, 2) }}</td>
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
  <div class="tot-row"><span>GST ({{ number_format($invoice->items->max('tax_rate') ?? 0, 0) }}%)</span><span>{{ number_format($invoice->tax_amount, 2) }}</span></div>
  @endif

  <hr class="double">

  <div class="grand-row">
    <span>Net Total ({{ $sym }})</span>
    <span>{{ number_format($invoice->net_total, 2) }}</span>
  </div>

  @if($invoice->paid_amount > 0)
  <hr class="dash">
  <div class="tot-row"><span>Paid Amount</span><span>{{ number_format($invoice->paid_amount, 2) }}</span></div>
  <div class="tot-row b"><span>Balance Due</span><span>{{ number_format($invoice->balance_due, 2) }}</span></div>
  @endif

  <hr class="dash">

  {{-- ── THIS INVOICE / OUTSTANDING ── --}}
  <div class="tot-row b"><span>THIS INVOICE&nbsp;&nbsp;:</span><span>{{ $sym }}&nbsp;{{ number_format($invoice->balance_due, 2) }}</span></div>
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
  <div class="barcode-wrap">
    <div class="barcode" id="barcode"></div>
    <div class="barcode-num">{{ $invoice->invoice_number }}</div>
  </div>

  <hr class="dash">

  {{-- ── FOOTER ── --}}
  <div class="footer">
    <div class="tagline">&#10022; THANK YOU FOR YOUR BUSINESS &#10022;</div>
    <div class="xs mt2">Goods once sold will not be taken back.</div>
    <div class="xs">Claims subject to local jurisdiction only.</div>
    <hr class="dash" style="margin:4px 0;">
    <!--div class="xs b">Return Policy</div>
    <div class="xs">Returns within 7 days with original receipt.</div>
    <div class="xs">Items must be unused &amp; in original packaging.</div-->
    <hr class="dash" style="margin:4px 0;">
    <div class="xs">Powered by <span class="b">WholeTrade POS</span></div>
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

/* ── hide cut-line on print ── */
window.addEventListener('beforeprint', function() {
  document.getElementById('cutLine').style.display = 'none';
});
window.addEventListener('afterprint', function() {
  document.getElementById('cutLine').style.display = '';
});
</script>
</body>
</html>
