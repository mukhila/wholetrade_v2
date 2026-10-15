<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0">
<title>Receipt</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:#111827;display:flex;justify-content:center;padding:16px 8px 40px;font-family:Arial,sans-serif}
.receipt{width:300px;max-width:calc(100vw - 16px);background:#fefefe;padding:6px 8px;font-size:11px;color:#000;line-height:1.45}
.tc{text-align:center}.tr{text-align:right}.b{font-weight:bold}
.xs{font-size:10px;line-height:1.5}.sm{font-size:10px}
.mt2{margin-top:2px}.mt4{margin-top:4px}.mt6{margin-top:6px}.mt8{margin-top:8px}
hr{border:none;margin:5px 0}
hr.dash{border-top:1px dashed #000}
hr.solid{border-top:1px solid #000}
hr.double{border-top:3px double #000}
.store-name{text-align:center;font-size:15px;font-weight:bold;letter-spacing:2px;text-transform:uppercase}
.store-sub{text-align:center;font-size:10px;line-height:1.55}
.inv-banner{text-align:center;font-size:12px;font-weight:bold;letter-spacing:3px;text-transform:uppercase;margin:3px 0}
.kv{display:flex;justify-content:space-between;font-size:10px;line-height:1.55}
.kv .k{font-weight:bold;white-space:nowrap;padding-right:3px}
.kv .v{text-align:right;font-size:10px}
.t-items{width:100%;border-collapse:collapse;font-size:10px;table-layout:fixed}
.t-items colgroup col:nth-child(1){width:45%}
.t-items colgroup col:nth-child(2){width:15%}
.t-items colgroup col:nth-child(3){width:20%}
.t-items colgroup col:nth-child(4){width:20%}
.t-items thead th{font-size:10px;font-weight:bold;text-align:left;padding:1px 0}
.t-items thead th.r{text-align:right}
.t-items tbody td{padding:0;vertical-align:top}
.t-items tbody td.r{text-align:right}
.item-code{font-weight:bold;font-size:10px;padding:2px 0 0;word-break:break-word}
.item-desc{font-size:9px;color:#555;padding:0 0 1px}
.item-vals td{padding-bottom:3px}
.item-sep td{border-top:1px dotted #ccc;font-size:1px;line-height:3px;height:3px;padding:1px 0}
.tot-row{display:flex;justify-content:space-between;font-size:10px;line-height:1.65}
.grand-row{display:flex;justify-content:space-between;font-size:15px;font-weight:bold;line-height:1.8}
.outstanding-row{display:flex;justify-content:space-between;font-size:11px;font-weight:bold;line-height:1.7;border-left:3px solid #000;padding-left:5px;margin:2px 0}
.pay-section{font-size:10px;line-height:1.6;padding:1px 0}
.pay-title{font-size:10px;font-weight:bold;text-decoration:underline;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px}
.qr-wrap{text-align:center;padding:4px 0 2px}
.qr-title{font-size:10px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px}
.qr-img{width:120px;height:120px;object-fit:contain;display:block;margin:0 auto;image-rendering:pixelated}
.qr-cta{font-size:10px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;margin-top:4px}
.qr-uen{font-size:10px;margin-top:2px}
.sig-wrap{display:flex;gap:10px;margin-top:10px}
.sig-box{flex:1;text-align:center}
.sig-line{border-bottom:1px solid #000;height:24px;margin-bottom:3px}
.sig-lbl{font-size:10px;line-height:1.4}
.footer{text-align:center;font-size:10px;line-height:1.65}
.status-badge{display:inline-block;font-size:10px;font-weight:bold;padding:1px 4px;border:1px solid #000;text-transform:uppercase;letter-spacing:1px}
</style>
</head>
<body>
<div class="receipt">
@php
  $cs  = $companySettings;
  $sym = $invoice->currency->symbol ?? '$';
@endphp

  <div class="store-name">{{ $cs->company_name ?? 'BUSINESS NAME' }}</div>
  <div class="store-sub mt2">
    {{ $cs->address ?? '' }}<br>
    @if($cs->phone_no ?? null)Ph: {{ $cs->phone_no }}@endif
    @if($cs->registration_no ?? null) | Co.Reg: {{ $cs->registration_no }}@endif<br>
    @if($cs->email ?? null){{ $cs->email }}@endif
    @if($cs->uen_no ?? null) | UEN: {{ $cs->uen_no }}@endif
  </div>

  <hr class="dash">
  <div class="inv-banner">&#9632;&nbsp;{{ $invoice->tax_amount > 0 ? 'TAX INVOICE' : 'INVOICE' }}&nbsp;&#9632;</div>
  <hr class="dash">

  <div class="kv"><span class="k">Invoice No  :</span><span class="v">{{ $invoice->invoice_number }}</span></div>
  <div class="kv"><span class="k">Date        :</span><span class="v">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</span></div>
  <div class="kv"><span class="k">Customer    :</span><span class="v">{{ $invoice->customer->name ?? 'WALK-IN' }}</span></div>

  @if(($invoice->customer->address_line_1 ?? '') || ($invoice->customer->address_line_2 ?? ''))
  <div class="kv"><span class="k">Address    :</span></div>
  @if($invoice->customer->address_line_1 ?? '')<div class="xs" style="padding-left:4px">{{ $invoice->customer->address_line_1 }}</div>@endif
  @if($invoice->customer->address_line_2 ?? '')<div class="xs" style="padding-left:4px">{{ $invoice->customer->address_line_2 }}</div>@endif
  @endif

  @if($invoice->salesperson)
  <div class="kv"><span class="k">Salesperson :</span><span class="v">{{ $invoice->salesperson->name }}</span></div>
  @endif
  @if($invoice->reference_number)
  <div class="kv"><span class="k">Reference   :</span><span class="v">{{ $invoice->reference_number }}</span></div>
  @endif

  @php $ps = $invoice->payment_status ?? 'unpaid'; @endphp
  <div class="kv mt2">
    <span class="k">Status      :</span>
    <span class="v">
      <span class="status-badge" style="
        @if($ps==='paid') border-color:#166534;color:#166534;background:#dcfce7;
        @elseif($ps==='partial') border-color:#92400e;color:#92400e;background:#fef3c7;
        @else border-color:#991b1b;color:#991b1b;background:#fee2e2;
        @endif
      ">{{ strtoupper($ps) }}</span>
    </span>
  </div>

  <hr class="dash">

  <table class="t-items">
    <colgroup><col><col><col><col></colgroup>
    <thead>
      <tr>
        <th>DESCRIPTION</th><th class="r">QTY</th><th class="r">PRICE</th><th class="r">AMT</th>
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
        @if($idx > 0)<tr class="item-sep"><td colspan="4"></td></tr>@endif
        <tr><td colspan="4" class="item-code">{{ $desc }}</td></tr>
        @if($subNote)<tr><td colspan="4" class="item-desc">{{ $subNote }}</td></tr>@endif
        <tr class="item-vals">
          <td></td>
          <td class="r">{{ $qtyFmt }}</td>
          <td class="r">{{ number_format($item->unit_price, 2) }}</td>
          <td class="r">{{ number_format($item->total_price, 2) }}</td>
        </tr>
        @if($hasDisc)
        <tr><td colspan="3" class="item-desc">Discount</td><td class="r item-desc">-{{ number_format($item->discount_amount, 2) }}</td></tr>
        @endif
      @endforeach
    </tbody>
  </table>

  <hr class="solid">

  <div class="tot-row"><span>Sub Total</span><span>{{ number_format($invoice->subtotal, 2) }}</span></div>
  @if($invoice->discount_amount > 0)
  <div class="tot-row"><span>Discount</span><span>-{{ number_format($invoice->discount_amount, 2) }}</span></div>
  @endif
  @if($invoice->tax_amount > 0)
  @php $taxBase = max(0, ($invoice->subtotal ?? 0) - ($invoice->discount_amount ?? 0)); $gstRate = $taxBase > 0 ? round($invoice->tax_amount / $taxBase * 100) : 0; @endphp
  <div class="tot-row"><span>GST ({{ $gstRate }}%)</span><span>{{ number_format($invoice->tax_amount, 2) }}</span></div>
  @endif

  <hr class="double">
  <div class="grand-row"><span>Net Total ({{ $sym }})</span><span>{{ number_format($invoice->net_total, 2) }}</span></div>

  @if(($invoice->paid_amount ?? 0) > 0)
  <hr class="dash">
  <div class="tot-row"><span>Paid Amount</span><span>{{ number_format($invoice->paid_amount, 2) }}</span></div>
  <div class="tot-row b"><span>Balance Due</span><span>{{ number_format($invoice->balance_due, 2) }}</span></div>
  @endif

  <hr class="dash">

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

  <div class="pay-section">
    <div class="pay-title">Payment Instructions</div>
    <div class="xs mt2">All cheques crossed &amp; payable to:</div>
    <div class="xs b mt2">{{ strtoupper($cs->company_name ?? 'BUSINESS NAME') }}</div>
    @if(($cs->bank_name ?? null) || ($cs->bank_account_no ?? null))
    <div class="xs b mt4">BANK TRANSFER:</div>
    @if($cs->bank_name ?? null)<div class="xs">Bank&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $cs->bank_name }}</div>@endif
    @if($cs->bank_account_no ?? null)<div class="xs">Acct No&nbsp;&nbsp;: {{ $cs->bank_account_no }}</div>@endif
    @if($cs->uen_no ?? null)<div class="xs">UEN No&nbsp;&nbsp;&nbsp;: {{ $cs->uen_no }}</div>@endif
    @endif
  </div>

  {{-- ── QR CODE (PayNow / Scan to Pay) ──
       Inlined as base64 so the Android WebView needs no network access
       and the bitmap is ready before the receipt is captured/printed. --}}
  @php
    $qrPath = public_path('assets/images/sgtrade_printqr.jpeg');
    if (!file_exists($qrPath)) {
        $qrPath = public_path('assets/images/qr.png');
    }
    $qrData = null;
    if (file_exists($qrPath)) {
        $qrMime = strtolower(pathinfo($qrPath, PATHINFO_EXTENSION)) === 'png'
            ? 'image/png'
            : 'image/jpeg';
        $qrData = 'data:' . $qrMime . ';base64,' . base64_encode(file_get_contents($qrPath));
    }
  @endphp
  @if($qrData)
  <hr class="dash">
  <div class="qr-wrap">
    <div class="qr-title">{{ strtoupper($cs->company_name ?? '') }}</div>
    <img class="qr-img" src="{{ $qrData }}" alt="Scan to Pay QR">
    <div class="qr-cta">&#9632; SCAN TO PAY &#9632;</div>
    @if($cs->uen_no ?? null)
    <div class="qr-uen">PayNow UEN: {{ $cs->uen_no }}</div>
    @endif
  </div>
  @endif

  <hr class="dash">

  <div class="sig-wrap">
    <div class="sig-box"><div class="sig-line"></div><div class="sig-lbl">Authorized Signature</div></div>
    <div class="sig-box"><div class="sig-line"></div><div class="sig-lbl">Customer Sign<br>&amp; Co. Stamp</div></div>
  </div>

  <hr class="dash" style="margin-top:10px">
  <div class="footer"><div class="xs">Powered by <span class="b">Techgeeks</span></div></div>

</div>
</body>
</html>
