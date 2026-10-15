@extends('reports.print.layout')
@section('title', 'Product Report')

@section('content')

{{-- Applied Filters Banner --}}
@php
    $activeFilters = array_filter($filters ?? [], fn($v) => $v !== '' && $v !== null);
@endphp
@if(count($activeFilters))
<div style="background:#f0f4ff;border:1px solid #c0d0f0;padding:8px 12px;margin-bottom:12px;font-size:10px;border-radius:4px;">
    <strong>Filters applied:</strong>
    @if(!empty($filters['search']))      &nbsp; Search: <em>{{ $filters['search'] }}</em> @endif
    @if(!empty($filters['category_id']))
        @php $cat = \App\Models\ItemCategory::withoutGlobalScope('subscriber')->find($filters['category_id']); @endphp
        @if($cat) &nbsp; Category: <em>{{ $cat->name }}</em> @endif
    @endif
    @if(!empty($filters['brand_id']))
        @php $brand = \App\Models\ProductBrand::withoutGlobalScope('subscriber')->find($filters['brand_id']); @endphp
        @if($brand) &nbsp; Brand: <em>{{ $brand->name }}</em> @endif
    @endif
    @if(!empty($filters['supplier_id']))
        @php $sup = \App\Models\Associate::withoutGlobalScope('subscriber')->find($filters['supplier_id']); @endphp
        @if($sup) &nbsp; Supplier: <em>{{ $sup->name }}</em> @endif
    @endif
    @if(!empty($filters['status']))      &nbsp; Status: <em>{{ $filters['status'] }}</em> @endif
    @if(!empty($filters['date_from']))   &nbsp; From: <em>{{ $filters['date_from'] }}</em> @endif
    @if(!empty($filters['date_to']))     &nbsp; To: <em>{{ $filters['date_to'] }}</em> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>SKU</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Brand</th>
            <th class="text-right">Cost Price</th>
            <th class="text-right">Sales Price</th>
            <th class="text-right">Margin %</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $i => $product)
            @php
                $costPrice  = $product->price->cost_price_item  ?? 0;
                $salesPrice = $product->price->sales_price_item ?? 0;
                $margin     = $costPrice > 0 ? (($salesPrice - $costPrice) / $costPrice) * 100 : 0;
                $marginColor = $margin >= 30 ? '#166534' : ($margin < 10 ? '#991b1b' : '#374151');
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->category->name ?? '-' }}</td>
                <td>{{ $product->brand->name    ?? '-' }}</td>
                <td class="text-right">{{ number_format($costPrice,  2) }}</td>
                <td class="text-right">{{ number_format($salesPrice, 2) }}</td>
                <td class="text-right" style="color:{{ $marginColor }};font-weight:bold;">{{ number_format($margin, 2) }}%</td>
                <td><span class="badge {{ $product->status ? 'bg-success' : 'bg-danger' }}">{{ $product->status ? 'Active' : 'Inactive' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center">No products found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <td colspan="8" class="text-right">TOTAL PRODUCTS IN THIS REPORT:</td>
            <td class="text-right" style="color:#90d4ff;">{{ $products->total() }}</td>
        </tr>
    </tfoot>
</table>

{{-- Summary Box --}}
<div style="margin-top:20px;display:flex;gap:16px;flex-wrap:wrap;">
    <div style="border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;min-width:320px;">
        <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
            PRODUCT SUMMARY {{ count($activeFilters) ? '— FILTERED DATA' : '— ALL DATA' }}
        </div>
        <table style="margin:0;width:100%;">
            <tr><th>Total Products</th>       <td><strong>{{ $kpis['total_products'] }}</strong></td></tr>
            <tr><th>Active Products</th>      <td style="color:#166534;">{{ $kpis['active_products'] }}</td></tr>
            <tr><th>Inactive Products</th>    <td style="color:#991b1b;">{{ $kpis['inactive_products'] }}</td></tr>
            <tr><th>High Margin (&gt;30%)</th><td style="color:#1e40af;">{{ $kpis['high_margin_products'] }}</td></tr>
            <tr><th>Low Margin (&lt;10%)</th> <td style="color:#b45309;">{{ $kpis['low_margin_products'] }}</td></tr>
        </table>
    </div>
</div>

@endsection
