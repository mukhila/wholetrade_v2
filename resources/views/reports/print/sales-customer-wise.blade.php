@extends('reports.print.layout')
@section('title', 'Sales Report — By Customer')

@section('content')
@php
    $activeFilters = array_filter($filters ?? [], fn($v) => $v !== '' && $v !== null && $v !== 'all' && $v !== 'current');
    $grandTotal    = 0;
    $grandPaid     = 0;
    $grandBalance  = 0;
    $grandCount    = 0;
@endphp

@if(count($activeFilters))
<div style="background:#f0f4ff;border:1px solid #c0d0f0;padding:8px 12px;margin-bottom:12px;font-size:10px;border-radius:4px;">
    <strong>Filters applied:</strong>
    @if(!empty($filters['date_from']))      &nbsp; From: <em>{{ $filters['date_from'] }}</em> @endif
    @if(!empty($filters['date_to']))        &nbsp; To: <em>{{ $filters['date_to'] }}</em> @endif
    @if(!empty($filters['salesperson_id']))
        @php $sp = \App\Models\User::find($filters['salesperson_id']); @endphp
        @if($sp) &nbsp; Salesperson: <em>{{ $sp->name }}</em> @endif
    @endif
    @if(!empty($filters['payment_status'])) &nbsp; Payment Status: <em>{{ ucfirst($filters['payment_status']) }}</em> @endif
    @if(!empty($filters['search']))         &nbsp; Search: <em>{{ $filters['search'] }}</em> @endif
</div>
@endif

@foreach($grouped as $customerName => $invoices)
@php
    $custTotal   = collect($invoices)->sum('total');
    $custPaid    = collect($invoices)->sum('paid_amount');
    $custBalance = collect($invoices)->sum('balance');
    $grandTotal   += $custTotal;
    $grandPaid    += $custPaid;
    $grandBalance += $custBalance;
    $grandCount   += count($invoices);
@endphp

{{-- Customer header --}}
<div style="background:#1a1a2e;color:#fff;padding:6px 12px;margin-top:20px;margin-bottom:0;border-radius:4px 4px 0 0;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-weight:bold;font-size:13px;">{{ $customerName }}</span>
    <span style="font-size:11px;color:#cbd5e1;">{{ count($invoices) }} invoice(s)</span>
</div>

<table style="margin-top:0;border-top:none;">
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Salesperson</th>
            <th class="text-right">Total</th>
            <th class="text-right">Paid</th>
            <th class="text-right">Balance</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $i => $invoice)
        @php $balance = $invoice['balance'] ?? 0; @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $invoice['invoice_number'] }}</td>
            <td>{{ \Carbon\Carbon::parse($invoice['invoice_date'])->format('d M Y') }}</td>
            <td>{{ $invoice['salesperson_name'] ?? '-' }}</td>
            <td class="text-right">{{ number_format($invoice['total'], 2) }}</td>
            <td class="text-right">{{ number_format($invoice['paid_amount'], 2) }}</td>
            <td class="text-right" style="color:{{ $balance > 0 ? '#dc2626' : '#166534' }};font-weight:bold;">
                {{ number_format($balance, 2) }}
            </td>
            <td><span class="badge bg-secondary">{{ $invoice['payment_status_display'] ?? $invoice['payment_status'] }}</span></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background:#f0f4ff;font-weight:bold;color:#1e3a5f;">
            <td colspan="4" class="text-right">Customer Subtotal:</td>
            <td class="text-right">{{ number_format($custTotal,   2) }}</td>
            <td class="text-right">{{ number_format($custPaid,    2) }}</td>
            <td class="text-right" style="color:{{ $custBalance > 0 ? '#dc2626' : '#166534' }};">
                {{ number_format($custBalance, 2) }}
            </td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endforeach

{{-- Grand total --}}
<table style="margin-top:24px;">
    <tfoot>
        <tr style="background:#f5f5f5;font-style:italic;color:#555;">
            <td colspan="4" class="text-right">Subtotal ({{ $grandCount }} invoices, {{ $grouped->count() }} customers):</td>
            <td class="text-right">{{ number_format($grandTotal,   2) }}</td>
            <td class="text-right">{{ number_format($grandPaid,    2) }}</td>
            <td class="text-right">{{ number_format($grandBalance, 2) }}</td>
            <td></td>
        </tr>
        <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
            <td colspan="4" class="text-right">&#9658; GRAND TOTAL:</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($kpis['total_sales_value'], 2) }}</td>
            <td class="text-right" style="color:#90d4ff;">{{ number_format($kpis['total_sales_value'] - $kpis['total_outstanding'], 2) }}</td>
            <td class="text-right" style="color:#fca5a5;">{{ number_format($kpis['total_outstanding'], 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- Summary box --}}
<div style="margin-top:20px;display:flex;gap:16px;flex-wrap:wrap;">
    <div style="border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;min-width:280px;">
        <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
            CUSTOMER-WISE SUMMARY
        </div>
        <table style="margin:0;width:100%;">
            <tr><th>Total Customers</th>  <td><strong>{{ $grouped->count() }}</strong></td></tr>
            <tr><th>Total Invoices</th>   <td><strong>{{ $grandCount }}</strong></td></tr>
            <tr><th>Total Sales</th>      <td style="color:#1e40af;font-weight:bold;">{{ number_format($kpis['total_sales_value'], 2) }}</td></tr>
            <tr><th>Total Collected</th>  <td style="color:#166534;">{{ number_format($kpis['total_sales_value'] - $kpis['total_outstanding'], 2) }}</td></tr>
            <tr><th>Total Outstanding</th><td style="color:#991b1b;font-weight:bold;">{{ number_format($kpis['total_outstanding'], 2) }}</td></tr>
        </table>
    </div>
</div>

@endsection
