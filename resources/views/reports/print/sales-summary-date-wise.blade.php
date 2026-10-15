@extends('reports.print.layout')
@section('title', 'Date-wise Sales Summary with Profit & Loss')

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
            <th>Date</th>
            <th class="text-right">Invoices</th>
            <th class="text-right">Total Sales</th>
            <th class="text-right">Total Paid</th>
            <th class="text-right">Outstanding</th>
            <th class="text-right">Revenue (ex-Tax)</th>
            <th class="text-right">COGS</th>
            <th class="text-right">Gross Profit/Loss</th>
            <th class="text-right">Profit %</th>
            <th class="text-right">Loss %</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
        @php $profit = (float) ($row['gross_profit'] ?? 0); @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['date'] }}</td>
            <td class="text-right">{{ $row['invoice_count'] }}</td>
            <td class="text-right">{{ number_format($row['total_sales'],   2) }}</td>
            <td class="text-right">{{ number_format($row['total_paid'],    2) }}</td>
            <td class="text-right" style="color:{{ ($row['total_balance'] ?? 0) > 0 ? '#dc2626' : '#166534' }};font-weight:bold;">
                {{ number_format($row['total_balance'], 2) }}
            </td>
            <td class="text-right">{{ number_format($row['total_revenue'], 2) }}</td>
            <td class="text-right" style="color:#dc2626;">{{ number_format($row['total_cost'],    2) }}</td>
            <td class="text-right" style="color:{{ $profit >= 0 ? '#166534' : '#dc2626' }};font-weight:bold;">
                {{ number_format($profit, 2) }}
            </td>
            <td class="text-right">
                @if(($row['profit_pct'] ?? 0) > 0)
                    <span style="background:#166534;color:#fff;padding:1px 5px;border-radius:3px;font-size:9px;">{{ $row['profit_pct'] }}%</span>
                @else
                    <span style="color:#999;">—</span>
                @endif
            </td>
            <td class="text-right">
                @if(($row['loss_pct'] ?? 0) > 0)
                    <span style="background:#dc2626;color:#fff;padding:1px 5px;border-radius:3px;font-size:9px;">{{ $row['loss_pct'] }}%</span>
                @else
                    <span style="color:#999;">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="11" class="text-center">No data found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#f5f5f5;font-style:italic;color:#555;">
            <td colspan="2" class="text-right">Subtotal ({{ count($rows) }} days):</td>
            <td class="text-right">{{ $totals['invoice_count'] }}</td>
            <td class="text-right">{{ number_format($totals['total_sales'],   2) }}</td>
            <td class="text-right">{{ number_format($totals['total_paid'],    2) }}</td>
            <td class="text-right">{{ number_format($totals['total_balance'], 2) }}</td>
            <td class="text-right">{{ number_format($totals['total_revenue'], 2) }}</td>
            <td class="text-right">{{ number_format($totals['total_cost'],    2) }}</td>
            <td class="text-right">{{ number_format($totals['gross_profit'],  2) }}</td>
            <td class="text-right">{{ $totals['profit_pct'] > 0 ? $totals['profit_pct'].'%' : '—' }}</td>
            <td class="text-right">{{ $totals['loss_pct']   > 0 ? $totals['loss_pct'].'%'   : '—' }}</td>
        </tr>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <td colspan="2" class="text-right">&#9658; GRAND TOTAL:</td>
            <td class="text-right" style="color:#90d4ff;">{{ $totals['invoice_count'] }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($totals['total_sales'],   2) }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($totals['total_paid'],    2) }}</td>
            <td class="text-right" style="color:#fca5a5;">{{ number_format($totals['total_balance'], 2) }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($totals['total_revenue'], 2) }}</td>
            <td class="text-right" style="color:#fca5a5;">{{ number_format($totals['total_cost'],    2) }}</td>
            <td class="text-right" style="{{ (float)$totals['gross_profit'] >= 0 ? 'color:#86efac' : 'color:#fca5a5' }}">
                {{ number_format($totals['gross_profit'], 2) }}
            </td>
            <td class="text-right" style="color:#86efac;">{{ $totals['profit_pct'] > 0 ? $totals['profit_pct'].'%' : '—' }}</td>
            <td class="text-right" style="color:#fca5a5;">{{ $totals['loss_pct']   > 0 ? $totals['loss_pct'].'%'   : '—' }}</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:20px;display:flex;gap:16px;flex-wrap:wrap;">
    <div style="border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;min-width:300px;">
        <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
            DATE-WISE SUMMARY
        </div>
        <table style="margin:0;width:100%;">
            <tr><th>Total Days</th>           <td><strong>{{ count($rows) }}</strong></td></tr>
            <tr><th>Total Invoices</th>        <td><strong>{{ $totals['invoice_count'] }}</strong></td></tr>
            <tr><th>Total Sales Value</th>     <td style="color:#1e40af;font-weight:bold;">{{ number_format($totals['total_sales'],   2) }}</td></tr>
            <tr><th>Total Collected</th>       <td style="color:#166534;">{{ number_format($totals['total_paid'],    2) }}</td></tr>
            <tr><th>Total Outstanding</th>     <td style="color:#991b1b;font-weight:bold;">{{ number_format($totals['total_balance'], 2) }}</td></tr>
            <tr><th>Revenue (ex-Tax)</th>      <td style="color:#1e40af;">{{ number_format($totals['total_revenue'], 2) }}</td></tr>
            <tr><th>Total COGS</th>            <td style="color:#991b1b;">{{ number_format($totals['total_cost'],    2) }}</td></tr>
            <tr><th>Gross Profit / Loss</th>   <td style="color:{{ (float)$totals['gross_profit'] >= 0 ? '#166534' : '#991b1b' }};font-weight:bold;">{{ number_format($totals['gross_profit'], 2) }}</td></tr>
            @if($totals['profit_pct'] > 0)
            <tr><th>Profit Margin</th>         <td style="color:#166534;font-weight:bold;">{{ $totals['profit_pct'] }}%</td></tr>
            @elseif($totals['loss_pct'] > 0)
            <tr><th>Loss %</th>                <td style="color:#991b1b;font-weight:bold;">{{ $totals['loss_pct'] }}%</td></tr>
            @endif
        </table>
    </div>
</div>

@endsection
