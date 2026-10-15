@extends('reports.print.layout')
@section('title', 'Customer-wise Detailed Report')

@section('extra_buttons')
<a href="{{ route('reports.customers.print-detailed-thermal', request()->query()) }}"
   target="_blank"
   style="padding:10px 18px;background:#065f46;color:#fff;border:none;cursor:pointer;text-decoration:none;font-size:13px;border-radius:3px;display:inline-flex;align-items:center;gap:6px;">
    &#128438; Thermal Print
</a>
@endsection

@section('content')
@php
    $grouped     = collect($detailed)->groupBy('customer_name');
    $grandTotal  = collect($detailed)->sum('net_total');
    $grandPaid   = collect($detailed)->sum('paid_amount');
    $grandBal    = collect($detailed)->sum('balance_due');
@endphp

@foreach($grouped as $customerName => $invoices)
<div style="margin-bottom:20px; page-break-inside:avoid;">
    <div style="background:#1a1a2e;color:#fff;padding:6px 10px;font-weight:bold;font-size:11px;border-radius:3px 3px 0 0;">
        {{ $customerName }}
        &nbsp;|&nbsp; {{ $invoices->count() }} invoice(s)
        &nbsp;|&nbsp; Total: {{ number_format($invoices->sum('net_total'), 2) }}
        &nbsp;|&nbsp; Balance: {{ number_format($invoices->sum('balance_due'), 2) }}
    </div>
    <table style="margin:0;">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice No</th>
                <th>Date</th>
                <th class="text-right">Total</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Balance</th>
                <th>Payment Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $j => $inv)
            <tr>
                <td>{{ $j + 1 }}</td>
                <td>{{ $inv['invoice_number'] }}</td>
                <td>{{ \Carbon\Carbon::parse($inv['invoice_date'])->format('d M Y') }}</td>
                <td class="text-right">{{ number_format($inv['net_total'], 2) }}</td>
                <td class="text-right">{{ number_format($inv['paid_amount'], 2) }}</td>
                <td class="text-right" style="color:{{ $inv['balance_due'] > 0 ? '#dc2626' : '#166534' }};font-weight:bold;">
                    {{ number_format($inv['balance_due'], 2) }}
                </td>
                <td><span class="badge bg-{{ $inv['payment_status'] === 'paid' ? 'success' : ($inv['payment_status'] === 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($inv['payment_status']) }}</span></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#f5f5f5;font-weight:bold;">
                <td colspan="3" class="text-right">Subtotal</td>
                <td class="text-right">{{ number_format($invoices->sum('net_total'), 2) }}</td>
                <td class="text-right">{{ number_format($invoices->sum('paid_amount'), 2) }}</td>
                <td class="text-right" style="color:#dc2626;">{{ number_format($invoices->sum('balance_due'), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endforeach

{{-- Grand Total --}}
<table style="margin-top:10px;">
    <tr style="background:#1a1a2e;color:#fff;font-weight:bold;">
        <td colspan="3" class="text-right" style="width:60%">&#9658; GRAND TOTAL ({{ collect($detailed)->count() }} invoices, {{ $grouped->count() }} customers)</td>
        <td class="text-right" style="color:#90d4ff;">{{ number_format($grandTotal, 2) }}</td>
        <td class="text-right" style="color:#90d4ff;">{{ number_format($grandPaid, 2) }}</td>
        <td class="text-right" style="color:#fca5a5;">{{ number_format($grandBal, 2) }}</td>
        <td></td>
    </tr>
</table>

{{-- Summary box --}}
<div style="margin-top:20px;border:2px solid #1a1a2e;border-radius:6px;overflow:hidden;max-width:350px;">
    <div style="background:#1a1a2e;color:#fff;text-align:center;padding:6px 12px;font-weight:bold;font-size:12px;letter-spacing:1px;">
        CUSTOMER SUMMARY
    </div>
    <table style="margin:0;width:100%;">
        @foreach($summary as $row)
        <tr>
            <th style="text-align:left;">{{ $row['customer_name'] }}</th>
            <td style="text-align:right;font-weight:bold;">{{ number_format($row['total_sales'], 2) }}</td>
            <td style="text-align:right;color:{{ $row['total_balance'] > 0 ? '#dc2626' : '#166534' }};">
                Bal: {{ number_format($row['total_balance'], 2) }}
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
