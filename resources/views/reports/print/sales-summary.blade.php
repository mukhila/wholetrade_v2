@extends('reports.print.layout')
@section('title', 'Sales Summary Report — ' . match($groupBy ?? 'customer') {
    'salesperson' => 'By Salesperson',
    'month'       => 'By Month',
    default       => 'By Customer',
})

@section('content')

@php
    $groupLabel = match($groupBy ?? 'customer') {
        'salesperson' => 'Salesperson',
        'month'       => 'Month',
        default       => 'Customer',
    };
    $activeFilters = array_filter($filters ?? [], fn($v) => $v !== '' && $v !== null && $v !== 'all' && $v !== 'customer');
@endphp

@if(count($activeFilters))
<div style="background:#f0f4ff;border:1px solid #c0d0f0;padding:8px 12px;margin-bottom:12px;font-size:10px;border-radius:4px;">
    <strong>Filters applied:</strong>
    @if(!empty($filters['date_from']))      &nbsp; From: <em>{{ $filters['date_from'] }}</em> @endif
    @if(!empty($filters['date_to']))        &nbsp; To: <em>{{ $filters['date_to'] }}</em> @endif
    @if(!empty($filters['customer_id']))
        @php $cust = \App\Models\Associate::withoutGlobalScope('subscriber')->find($filters['customer_id']); @endphp
        @if($cust) &nbsp; Customer: <em>{{ $cust->name }}</em> @endif
    @endif
    @if(!empty($filters['salesperson_id']))
        @php $sp = \App\Models\User::find($filters['salesperson_id']); @endphp
        @if($sp) &nbsp; Salesperson: <em>{{ $sp->name }}</em> @endif
    @endif
    @if(!empty($filters['payment_status'])) &nbsp; Payment Status: <em>{{ ucfirst($filters['payment_status']) }}</em> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ $groupLabel }}</th>
            <th class="text-right">Invoices</th>
            <th class="text-right">Total Sales</th>
            <th class="text-right">Total Paid</th>
            <th class="text-right">Outstanding</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['group_name'] }}</td>
            <td class="text-right">{{ $row['invoice_count'] }}</td>
            <td class="text-right">{{ number_format($row['total_sales'], 2) }}</td>
            <td class="text-right">{{ number_format($row['total_paid'], 2) }}</td>
            <td class="text-right" style="color:{{ $row['total_balance'] > 0 ? '#dc2626' : '#166534' }};font-weight:bold;">
                {{ number_format($row['total_balance'], 2) }}
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center">No data found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#f5f5f5;font-style:italic;color:#555;">
            <td colspan="2" class="text-right">Subtotal ({{ count($rows) }} {{ strtolower($groupLabel) }}s):</td>
            <td class="text-right">{{ $totals['invoice_count'] }}</td>
            <td class="text-right">{{ number_format($totals['total_sales'], 2) }}</td>
            <td class="text-right">{{ number_format($totals['total_paid'], 2) }}</td>
            <td class="text-right">{{ number_format($totals['total_balance'], 2) }}</td>
        </tr>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <td colspan="2" class="text-right">&#9658; GRAND TOTAL:</td>
            <td class="text-right" style="color:#90d4ff;">{{ $totals['invoice_count'] }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($totals['total_sales'], 2) }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($totals['total_paid'], 2) }}</td>
            <td class="text-right" style="color:#fca5a5;">{{ number_format($totals['total_balance'], 2) }}</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:20px;display:flex;gap:16px;flex-wrap:wrap;">
    <div style="border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;min-width:280px;">
        <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
            SALES SUMMARY
        </div>
        <table style="margin:0;width:100%;">
            <tr><th>Total {{ $groupLabel }}s</th><td><strong>{{ count($rows) }}</strong></td></tr>
            <tr><th>Total Invoices</th>          <td><strong>{{ $totals['invoice_count'] }}</strong></td></tr>
            <tr><th>Total Sales Value</th>        <td style="color:#1e40af;font-weight:bold;">{{ number_format($totals['total_sales'], 2) }}</td></tr>
            <tr><th>Total Collected</th>          <td style="color:#166534;">{{ number_format($totals['total_paid'], 2) }}</td></tr>
            <tr><th>Total Outstanding</th>        <td style="color:#991b1b;font-weight:bold;">{{ number_format($totals['total_balance'], 2) }}</td></tr>
        </table>
    </div>
</div>

@endsection
