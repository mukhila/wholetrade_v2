@extends('reports.print.layout')
@section('title', 'Sales Report')

@section('content')

{{-- Applied Filters Banner --}}
@php
    $activeFilters = array_filter($filters ?? [], fn($v) => $v !== '' && $v !== null && $v !== 'all');
@endphp
@if(count($activeFilters))
<div style="background:#f0f4ff;border:1px solid #c0d0f0;padding:8px 12px;margin-bottom:12px;font-size:10px;border-radius:4px;">
    <strong>Filters applied:</strong>
    @if(!empty($filters['search']))         &nbsp; Search: <em>{{ $filters['search'] }}</em> @endif
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
    @if(!empty($filters['source']) && $filters['source'] !== 'all') &nbsp; Source: <em>{{ ucfirst($filters['source']) }}</em> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Source</th>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Salesperson</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Total</th>
            <th class="text-right">Paid</th>
            <th class="text-right">Balance</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $i => $invoice)
            @php
                $balance = $invoice['balance'] ?? 0;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><span class="badge {{ $invoice['source'] === 'current' ? 'bg-primary' : 'bg-info' }}">{{ ucfirst($invoice['source']) }}</span></td>
                <td>{{ $invoice['invoice_number'] }}</td>
                <td>{{ \Carbon\Carbon::parse($invoice['invoice_date'])->format('d M Y') }}</td>
                <td>{{ $invoice['customer_name'] }}</td>
                <td>{{ $invoice['salesperson_name'] ?? '-' }}</td>
                <td class="text-right">{{ $invoice['total_qty'] ?? '—' }}</td>
                <td class="text-right">{{ number_format($invoice['total'], 2) }}</td>
                <td class="text-right">{{ number_format($invoice['paid_amount'], 2) }}</td>
                <td class="text-right" style="color:{{ $balance > 0 ? '#dc2626' : '#166534' }};font-weight:bold;">
                    {{ number_format($balance, 2) }}
                </td>
                <td><span class="badge bg-secondary">{{ $invoice['payment_status_display'] ?? $invoice['payment_status'] }}</span></td>
            </tr>
        @empty
            <tr><td colspan="11" class="text-center">No invoices found.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        @php
            $grandTotal   = collect($invoices)->sum('total');
            $grandPaid    = collect($invoices)->sum('paid_amount');
            $grandBalance = collect($invoices)->sum('balance');
            $grandQty     = collect($invoices)->sum('total_qty');
        @endphp
        <tr style="background:#f5f5f5;font-style:italic;color:#555;">
            <td colspan="6" class="text-right">Subtotal ({{ count($invoices) }} invoices printed):</td>
            <td class="text-right">{{ $grandQty }}</td>
            <td class="text-right">{{ number_format($grandTotal,   2) }}</td>
            <td class="text-right">{{ number_format($grandPaid,    2) }}</td>
            <td class="text-right">{{ number_format($grandBalance, 2) }}</td>
            <td></td>
        </tr>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <td colspan="6" class="text-right">&#9658; GRAND TOTAL ({{ count($invoices) }} invoices):</td>
            <td class="text-right" style="color:#90d4ff;">{{ $grandQty }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($kpis['total_sales_value'], 2) }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($kpis['total_sales_value'] - $kpis['total_outstanding'], 2) }}</td>
            <td class="text-right" style="color:#fca5a5;">{{ number_format($kpis['total_outstanding'], 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- Summary Box --}}
<div style="margin-top:20px;display:flex;gap:16px;flex-wrap:wrap;">
    <div style="border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;min-width:300px;">
        <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
            SALES SUMMARY {{ count($activeFilters) ? '— FILTERED DATA' : '— ALL DATA' }}
        </div>
        <table style="margin:0;width:100%;">
            <tr><th>Total Invoices</th>     <td><strong>{{ $kpis['total_invoices'] }}</strong></td></tr>
            <tr><th>Total Sales Value</th>  <td style="color:#1e40af;font-weight:bold;">{{ number_format($kpis['total_sales_value'], 2) }}</td></tr>
            <tr><th>Total Collected</th>    <td style="color:#166534;">{{ number_format($kpis['total_sales_value'] - $kpis['total_outstanding'], 2) }}</td></tr>
            <tr><th>Total Outstanding</th>  <td style="color:#991b1b;font-weight:bold;">{{ number_format($kpis['total_outstanding'], 2) }}</td></tr>
            <tr><th>Avg Order Value</th>    <td>{{ number_format($kpis['avg_order_value'], 2) }}</td></tr>
            @if(!empty($kpis['top_customers']))
            <tr>
                <th>Top Customer</th>
                <td>{{ $kpis['top_customers'][0]['customer_name'] ?? '-' }}
                    ({{ number_format($kpis['top_customers'][0]['total_sales'] ?? 0, 2) }})</td>
            </tr>
            @endif
        </table>
    </div>
</div>

@endsection
