@extends('reports.print.layout')
@section('title', 'Stock Report')

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
    @if(!empty($filters['inventory_status'])) &nbsp; Status: <em>{{ ucwords(str_replace('_', ' ', $filters['inventory_status'])) }}</em> @endif
    @if(!empty($filters['batch_no']))    &nbsp; Batch/Lot: <em>{{ $filters['batch_no'] }}</em> @endif
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
            <th class="text-center">Stock in Hand</th>
            <th class="text-center">Min Stock</th>
            <th class="text-right">Stock Value</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inventory as $i => $item)
            @php
                $status = 'Normal'; $color = 'success';
                if ($item->stock_in_hand <= 0) {
                    $status = 'Out of Stock'; $color = 'danger';
                } elseif ($item->stock_in_hand <= $item->product['min_stock_qty']) {
                    $status = 'Low Stock'; $color = 'warning';
                }
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product['sku'] }}</td>
                <td>{{ $item->product['name'] }}</td>
                <td>{{ $item->product['category']['name'] ?? '-' }}</td>
                <td>{{ $item->product['brand']['name']    ?? '-' }}</td>
                <td class="text-center">{{ number_format($item->stock_in_hand) }}</td>
                <td class="text-center">{{ $item->product['min_stock_qty'] }}</td>
                <td class="text-right">{{ number_format($item->stock_value, 2) }}</td>
                <td><span class="badge bg-{{ $color }}">{{ $status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center">No inventory records found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <td colspan="7" class="text-right">GRAND TOTAL ({{ $inventory->count() }} items):</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($kpis['total_stock_value'], 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- Summary Box --}}
<div style="margin-top:20px;display:flex;gap:16px;flex-wrap:wrap;">
    <div style="border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;min-width:260px;">
        <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
            STOCK SUMMARY {{ count($activeFilters) ? '— FILTERED DATA' : '— ALL DATA' }}
        </div>
        <table style="margin:0;width:100%;">
            <tr><th>Total Records Printed</th><td>{{ $inventory->count() }}</td></tr>
            <tr><th>Total Stock Qty</th>      <td>{{ number_format($kpis['total_stock_qty']) }}</td></tr>
            <tr><th>Total Stock Value</th>    <td><strong>{{ number_format($kpis['total_stock_value'], 2) }}</strong></td></tr>
            <tr><th>Low Stock Items</th>      <td style="color:#b45309;">{{ $kpis['low_stock_count'] }}</td></tr>
            <tr><th>Out of Stock Items</th>   <td style="color:#dc2626;">{{ $kpis['out_of_stock_count'] }}</td></tr>
        </table>
    </div>
</div>

@endsection
