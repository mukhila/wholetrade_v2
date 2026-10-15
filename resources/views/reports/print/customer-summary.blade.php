@extends('reports.print.layout')
@section('title', 'Customer-wise Summary Report')

@section('content')
@php
    $grandSales   = collect($summary)->sum('total_sales');
    $grandPaid    = collect($summary)->sum('total_paid');
    $grandBalance = collect($summary)->sum('total_balance');
    $grandCount   = collect($summary)->sum('invoice_count');
@endphp

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Customer</th>
            <th class="text-right">Invoices</th>
            <th class="text-right">Total Sales</th>
            <th class="text-right">Total Paid</th>
            <th class="text-right">Balance Due</th>
            <th class="text-right">Paid %</th>
        </tr>
    </thead>
    <tbody>
        @forelse($summary as $i => $row)
        @php $paidPct = $row['total_sales'] > 0 ? ($row['total_paid'] / $row['total_sales']) * 100 : 0; @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['customer_name'] }}</td>
            <td class="text-right">{{ $row['invoice_count'] }}</td>
            <td class="text-right">{{ number_format($row['total_sales'], 2) }}</td>
            <td class="text-right" style="color:#166534;">{{ number_format($row['total_paid'], 2) }}</td>
            <td class="text-right" style="color:{{ $row['total_balance'] > 0 ? '#dc2626' : '#166534' }};font-weight:{{ $row['total_balance'] > 0 ? 'bold' : 'normal' }};">
                {{ number_format($row['total_balance'], 2) }}
            </td>
            <td class="text-right">{{ number_format($paidPct, 1) }}%</td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center">No data found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <td colspan="2">&#9658; GRAND TOTAL ({{ collect($summary)->count() }} customers)</td>
            <td class="text-right">{{ $grandCount }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($grandSales, 2) }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($grandPaid, 2) }}</td>
            <td class="text-right" style="color:#fca5a5;">{{ number_format($grandBalance, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- Summary Stats Box --}}
<div style="margin-top:20px;display:flex;gap:16px;">
    <div style="border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;min-width:280px;">
        <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
            OVERALL SUMMARY
        </div>
        <table style="margin:0;width:100%;">
            <tr><th>Total Customers</th><td><strong>{{ collect($summary)->count() }}</strong></td></tr>
            <tr><th>Total Invoices</th><td><strong>{{ $grandCount }}</strong></td></tr>
            <tr><th>Total Sales</th><td style="color:#1e40af;font-weight:bold;">{{ number_format($grandSales, 2) }}</td></tr>
            <tr><th>Total Collected</th><td style="color:#166534;">{{ number_format($grandPaid, 2) }}</td></tr>
            <tr><th>Total Outstanding</th><td style="color:#991b1b;font-weight:bold;">{{ number_format($grandBalance, 2) }}</td></tr>
            @if($grandSales > 0)
            <tr>
                <th>Collection Rate</th>
                <td>{{ number_format(($grandPaid / $grandSales) * 100, 1) }}%</td>
            </tr>
            @endif
        </table>
    </div>
</div>
@endsection
