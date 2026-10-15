<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock List</title>
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
        * { box-sizing: border-box; }
        body {
            font-family: 'Noto Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            margin: 0;
            padding: 15px;
            color: #222;
        }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0 0 4px; font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .header p  { margin: 2px 0; color: #555; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; font-size: 10px; }
        th { background-color: #f0f0f0; font-weight: bold; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        tfoot .subtotal-row { background: #f5f5f5; font-style: italic; color: #555; font-size: 9px; }
        tfoot .grand-total-row { background: #1a1a2e; color: #fff; font-weight: bold; font-size: 10px; }
        tfoot .grand-total-row .cost-col { color: #90c8ff; }
        tfoot .grand-total-row .qty-col  { color: #c4b5fd; }
        .grand-total-banner { margin-top: 20px; border: 2px solid #1a1a2e; border-radius: 4px; overflow: hidden; }
        .grand-total-banner .banner-header { background: #1a1a2e; color: #fff; text-align: center; padding: 6px 12px; font-size: 11px; font-weight: bold; letter-spacing: 1px; }
        .grand-total-banner .banner-body { display: flex; }
        .grand-total-banner .banner-cell { flex: 1; padding: 10px 14px; text-align: center; border-right: 1px solid #ddd; }
        .grand-total-banner .banner-cell:last-child { border-right: none; }
        .banner-label { font-size: 9px; color: #777; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .banner-value { font-size: 14px; font-weight: bold; }
        .banner-value.cost { color: #0d47a1; }
        .banner-value.qty  { color: #4a148c; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
        .no-print { margin-bottom: 15px; text-align: right; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none; }
            body { padding: 0; font-family: 'Noto Sans', Arial, sans-serif; font-size: 10px; line-height: 1.5; }
            @page { margin: 1cm; size: A4 landscape; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print">
    <button onclick="window.print()" style="padding:8px 20px;background:#007bff;color:#fff;border:none;cursor:pointer;border-radius:4px;">
        &#128438; Print / Save as PDF
    </button>
    <a href="{{ route('stock-list.export', request()->query()) }}"
       style="padding:8px 20px;background:#28a745;color:#fff;border:none;cursor:pointer;border-radius:4px;text-decoration:none;margin-left:8px;">
        &#9660; Export CSV
    </a>
</div>

<div class="header">
    <h1>Stock List</h1>
    <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
    @if(!empty($filters) && array_filter($filters))
        <p>
            @if(!empty($filters['search']))   Search: <strong>{{ $filters['search'] }}</strong> &nbsp; @endif
            @if(!empty($filters['category_id']))
                @php $catName = \App\Models\ItemCategory::withoutGlobalScope('subscriber')->find($filters['category_id'])?->name; @endphp
                @if($catName) Category: <strong>{{ $catName }}</strong> &nbsp; @endif
            @endif
            @if(!empty($filters['brand_id']))
                @php $brandName = \App\Models\ProductBrand::withoutGlobalScope('subscriber')->find($filters['brand_id'])?->name; @endphp
                @if($brandName) Brand: <strong>{{ $brandName }}</strong> @endif
            @endif
        </p>
    @endif
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>SKU</th>
            <th>Product Name</th>
            <th class="text-right">Cost/Item</th>
            <th class="text-right">Sell/Item</th>
            <th class="text-center">Qty (Loose)</th>
            <th class="text-right">Total Cost</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $i => $product)
            @php
                $qtyPcs    = max(0, $product->stock_qty_pcs ?? 0);
                $costItem  = $product->price?->cost_price_item  ?? 0;
                $sellItem  = $product->price?->sales_price_item ?? 0;
                $totalCost = $qtyPcs * $costItem;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->name }}</td>
                <td class="text-right">{{ number_format($costItem, 2) }}</td>
                <td class="text-right">{{ number_format($sellItem, 2) }}</td>
                <td class="text-center">{{ number_format($qtyPcs, 0) }}</td>
                <td class="text-right fw-bold">{{ number_format($totalCost, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center">No products found.</td></tr>
        @endforelse
    </tbody>
    @if($products->count() > 0)
    @php
        $printQtyPcs = $products->sum(fn($p) => max(0, $p->stock_qty_pcs ?? 0));
        $printCost   = $products->sum(fn($p) => max(0, $p->stock_qty_pcs ?? 0) * ($p->price?->cost_price_item ?? 0));
    @endphp
    <tfoot>
        <tr class="subtotal-row">
            <td colspan="5" class="text-right">Total ({{ $products->count() }} products listed):</td>
            <td class="text-center">{{ number_format($printQtyPcs, 0) }}</td>
            <td class="text-right">{{ number_format($printCost, 2) }}</td>
        </tr>
        <tr class="grand-total-row">
            <td colspan="5" class="text-right">&#9658; GRAND TOTAL &mdash; ALL {{ $products->count() }} PRODUCTS:</td>
            <td class="text-center qty-col">{{ number_format($totalQtyPcs, 0) }}</td>
            <td class="text-right cost-col">{{ number_format($totalCostValue, 2) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Grand Total Banner --}}
<div class="grand-total-banner">
    <div class="banner-header">&#9733; STOCK GRAND TOTALS &mdash; ALL {{ $products->count() }} PRODUCTS &#9733;</div>
    <div class="banner-body">
        <div class="banner-cell">
            <div class="banner-label">Total Products</div>
            <div class="banner-value" style="color:#333;">{{ number_format($products->count()) }}</div>
        </div>
        <div class="banner-cell">
            <div class="banner-label">Total Qty (Loose)</div>
            <div class="banner-value qty">{{ number_format($totalQtyPcs, 0) }}</div>
        </div>
        <div class="banner-cell">
            <div class="banner-label">Total Cost Value</div>
            <div class="banner-value cost">{{ number_format($totalCostValue, 2) }}</div>
        </div>
    </div>
</div>

<div class="footer">
    <p>&copy; {{ date('Y') }} Whole Trade. All rights reserved. &mdash; Stock List Report</p>
</div>

</body>
</html>
