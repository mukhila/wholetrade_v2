<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Invoice — {{ $invoice->invoice_number }}</title>
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
        body { font-family: 'Noto Sans', Arial, sans-serif; font-size: 10px; line-height: 1.5; margin: 0; padding: 20px; color: #333; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 12px; }
        .company-name { font-size: 18px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .inv-title { font-size: 11px; font-weight: bold; color: #555; text-align: right; text-transform: uppercase; letter-spacing: 2px; }
        .inv-number { font-size: 16px; font-weight: bold; color: #2563eb; text-align: right; }
        .addresses { display: flex; gap: 30px; margin-bottom: 16px; }
        .address-block h6 { font-size: 9px; text-transform: uppercase; color: #888; margin-bottom: 4px; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #ddd; padding: 4px 8px; text-align: left; font-size: 10px; }
        th { background: #f3f4f6; font-weight: bold; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 280px; margin-left: auto; }
        .totals-table td { border: none; padding: 3px 6px; font-size: 10px; }
        .net-total td { font-size: 14px; font-weight: bold; border-top: 2px solid #333; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 9px; color: #fff; font-weight: bold; }
        .badge-success { background: #16a34a; }
        .badge-warning { background: #d97706; color: #333; }
        .badge-danger  { background: #dc2626; }
        .badge-info    { background: #0891b2; }
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 9px; color: #888; text-align: center; line-height: 1.5; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none; }
            body { padding: 0; font-family: 'Noto Sans', Arial, sans-serif; font-size: 10px; line-height: 1.5; }
            @page { margin: 10mm 12mm; size: A4; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="text-align:right; margin-bottom:15px;">
        <button onclick="window.print()" style="padding:8px 20px; background:#2563eb; color:#fff; border:none; border-radius:4px; cursor:pointer;">
            Print
        </button>
    </div>

    <div class="header">
        <div>
            <div class="company-name">{{ config('app.name', 'Whole Trade') }}</div>
            <div style="color:#888; font-size:11px; margin-top:4px;">Supplier Invoice</div>
        </div>
        <div style="text-align:right;">
            <div class="inv-title">SUPPLIER INVOICE</div>
            <div class="inv-number">{{ $invoice->invoice_number }}</div>
            <div style="margin-top:8px; font-size:11px; color:#555;">
                Date: <strong>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</strong><br>
                @if($invoice->due_date)
                Due: <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</strong><br>
                @endif
                @if($invoice->supplier_ref_number)
                Supplier Ref: <strong>{{ $invoice->supplier_ref_number }}</strong><br>
                @endif
            </div>
            <div style="margin-top:6px;">
                @if($invoice->payment_status === 'paid')
                    <span class="badge badge-success">PAID</span>
                @elseif($invoice->payment_status === 'partial')
                    <span class="badge badge-info">PARTIAL</span>
                @else
                    <span class="badge badge-danger">UNPAID</span>
                @endif
            </div>
        </div>
    </div>

    <div class="addresses">
        <div class="address-block">
            <h6>Supplier</h6>
            <strong>{{ $invoice->supplier->name ?? 'N/A' }}</strong><br>
            @if($invoice->supplier->code)<span style="color:#888;">{{ $invoice->supplier->code }}</span><br>@endif
            @if($invoice->billing_address){!! nl2br(e($invoice->billing_address)) !!}@endif
        </div>
    </div>

    @php $showGst = isset($companySettings) && $companySettings->gst_include === 'Yes'; @endphp
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Description</th>
                <th class="text-center">Box Qty</th>
                <th class="text-center">Pcs Qty</th>
                <th class="text-center">Total Qty</th>
                <th class="text-right">Unit Price</th>
                @if($showGst)<th class="text-right">GST</th>@endif
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $item->product->sku ?? '-' }}</strong></td>
                <td>
                    {{ $item->product->name ?? $item->item_description }}
                    @if($item->product?->uom || $item->product?->packSize)
                        <span style="font-size:8px;color:#888;display:block;margin-top:2px;">
                            @if($item->product->uom) UOM: {{ $item->product->uom->name }}@endif
                            @if($item->product->packSize) &nbsp;| {{ $item->product->packSize->name }}@endif
                        </span>
                    @endif
                </td>
                <td class="text-center">{{ $item->box_qty }}</td>
                <td class="text-center">{{ $item->item_qty }}</td>
                <td class="text-center">{{ $item->total_qty }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                @if($showGst)<td class="text-right">{{ number_format($item->tax_amount, 2) }}</td>@endif
                <td class="text-right"><strong>{{ number_format($item->total_price, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr><td>Subtotal</td><td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
        @if($invoice->discount_amount > 0)
        <tr><td>Discount</td><td class="text-right" style="color:#dc2626;">-{{ number_format($invoice->discount_amount, 2) }}</td></tr>
        @endif
        @if($showGst && $invoice->tax_amount > 0)
        <tr><td>GST</td><td class="text-right">{{ number_format($invoice->tax_amount, 2) }}</td></tr>
        @endif
        <tr class="net-total">
            <td>Net Total ({{ $invoice->currency->symbol ?? '$' }})</td>
            <td class="text-right">{{ number_format($invoice->net_total, 2) }}</td>
        </tr>
        <tr><td style="color:#16a34a;">Paid Amount</td><td class="text-right" style="color:#16a34a;">{{ number_format($invoice->paid_amount, 2) }}</td></tr>
        @if($invoice->balance_due > 0)
        <tr><td style="color:#dc2626; font-weight:bold;">Balance Due</td><td class="text-right" style="color:#dc2626; font-weight:bold;">{{ number_format($invoice->balance_due, 2) }}</td></tr>
        @endif
    </table>

    @if($invoice->remarks)
    <div style="margin-top:20px; padding:10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:4px;">
        <strong>Remarks:</strong> {{ $invoice->remarks }}
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp; {{ config('app.name', 'Whole Trade') }}</p>
    </div>
</body>
</html>
