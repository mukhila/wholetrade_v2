@extends('reports.print.layout')
@section('title', 'Profit & Loss Report')

@section('content')
@php
    $activeFilters = array_filter($filters ?? [], fn($v) => $v !== '' && $v !== null && $v !== 'all');
@endphp

@if(count($activeFilters))
<div style="background:#f0f4ff;border:1px solid #c0d0f0;padding:8px 12px;margin-bottom:12px;font-size:10px;border-radius:4px;">
    <strong>Filters applied:</strong>
    @if(!empty($filters['date_from']))      &nbsp; From: <em>{{ $filters['date_from'] }}</em> @endif
    @if(!empty($filters['date_to']))        &nbsp; To: <em>{{ $filters['date_to'] }}</em> @endif
    @if(!empty($filters['customer_id']))
        @php $cust = \App\Models\Associate::find($filters['customer_id']); @endphp
        @if($cust) &nbsp; Customer: <em>{{ $cust->name }}</em> @endif
    @endif
    @if(!empty($filters['salesperson_id']))
        @php $sp = \App\Models\User::find($filters['salesperson_id']); @endphp
        @if($sp) &nbsp; Salesperson: <em>{{ $sp->name }}</em> @endif
    @endif
    @if(!empty($filters['search'])) &nbsp; Search: <em>{{ $filters['search'] }}</em> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Salesperson</th>
            <th class="text-right">Invoice Total</th>
            <th class="text-right">Revenue (ex-Tax)</th>
            <th class="text-right">COGS</th>
            <th class="text-right">Gross Profit</th>
            <th class="text-right">Margin %</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['invoice_number'] }}</td>
            <td>{{ $row['invoice_date'] }}</td>
            <td>{{ $row['customer_name'] }}</td>
            <td>{{ $row['salesperson_name'] }}</td>
            <td class="text-right">{{ number_format($row['net_total'],    2) }}</td>
            <td class="text-right">{{ number_format($row['revenue'],      2) }}</td>
            <td class="text-right">{{ number_format($row['total_cost'],   2) }}</td>
            <td class="text-right" style="color:{{ $row['gross_profit'] >= 0 ? '#166534' : '#dc2626' }};font-weight:bold;">
                {{ number_format($row['gross_profit'], 2) }}
            </td>
            <td class="text-right" style="color:{{ $row['margin_pct'] >= 0 ? '#166534' : '#dc2626' }};">
                {{ $row['margin_pct'] }}%
            </td>
        </tr>
        @empty
        <tr><td colspan="10" class="text-center">No data found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#f5f5f5;font-style:italic;color:#555;">
            <td colspan="4" class="text-right">Subtotal ({{ count($rows) }} invoices):</td>
            <td></td>
            <td></td>
            <td class="text-right">{{ number_format($totals['totalRev'],    2) }}</td>
            <td class="text-right">{{ number_format($totals['totalCost'],   2) }}</td>
            <td class="text-right">{{ number_format($totals['totalProfit'], 2) }}</td>
            <td class="text-right">{{ $totals['margin_pct'] }}%</td>
        </tr>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <td colspan="4" class="text-right">&#9658; GRAND TOTAL ({{ $totals['invoiceCount'] }} invoices):</td>
            <td></td>
            <td></td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($totals['totalRev'],    2) }}</td>
            <td class="text-right" style="color:#fca5a5;">{{ number_format($totals['totalCost'],   2) }}</td>
            <td class="text-right" style="color:{{ $totals['totalProfit'] >= 0 ? '#86efac' : '#fca5a5' }};">
                {{ number_format($totals['totalProfit'], 2) }}
            </td>
            <td class="text-right" style="color:{{ $totals['margin_pct'] >= 0 ? '#86efac' : '#fca5a5' }};">
                {{ $totals['margin_pct'] }}%
            </td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:20px;display:flex;gap:16px;flex-wrap:wrap;">
    <div style="border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;min-width:300px;">
        <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
            PROFIT & LOSS SUMMARY
        </div>
        <table style="margin:0;width:100%;">
            <tr><th>Total Invoices</th>    <td><strong>{{ $totals['invoiceCount'] }}</strong></td></tr>
            <tr><th>Total Revenue (ex-Tax)</th><td style="color:#1e40af;font-weight:bold;">{{ number_format($totals['totalRev'], 2) }}</td></tr>
            <tr><th>Total COGS</th>         <td style="color:#991b1b;">{{ number_format($totals['totalCost'],   2) }}</td></tr>
            <tr><th>Gross Profit</th>       <td style="color:{{ $totals['totalProfit'] >= 0 ? '#166534' : '#dc2626' }};font-weight:bold;">{{ number_format($totals['totalProfit'], 2) }}</td></tr>
            <tr><th>Gross Margin</th>       <td style="color:{{ $totals['margin_pct'] >= 0 ? '#166534' : '#dc2626' }};font-weight:bold;">{{ $totals['margin_pct'] }}%</td></tr>
        </table>
    </div>
</div>

@endsection
