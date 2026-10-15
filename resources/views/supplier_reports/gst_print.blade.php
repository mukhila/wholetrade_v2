@extends('reports.print.layout')
@section('title', 'GST Report — Supplier Payments')

@section('content')
@php
    $cs = $companySetting;
@endphp

{{-- Company Header --}}
<div style="text-align:center; margin-bottom:16px;">
    @if($cs && $cs->company_name)
        <div style="font-size:18px; font-weight:bold; text-transform:uppercase; letter-spacing:1px;">
            {{ $cs->company_name }}
        </div>
    @endif
    @if($cs && $cs->address)
        <div style="font-size:11px; color:#555;">{{ $cs->address }}</div>
    @endif
    @if($cs && $cs->phone_no)
        <div style="font-size:11px; color:#555;">Tel: {{ $cs->phone_no }}</div>
    @endif
    @if($cs && $cs->uen_no)
        <div style="font-size:11px; color:#555;">UEN: {{ $cs->uen_no }}</div>
    @endif
</div>

<div style="border-top:2px solid #000; border-bottom:1px solid #000; padding:6px 0; text-align:center; margin-bottom:14px;">
    <div style="font-size:15px; font-weight:bold; letter-spacing:2px; text-transform:uppercase;">
        GST Report — Supplier Payments
    </div>
    <div style="font-size:10px; color:#555; margin-top:3px;">
        {{ $filterLabel }} &nbsp;|&nbsp; Printed: {{ now()->format('d M Y, h:i A') }}
    </div>
</div>

{{-- Summary Box --}}
<table style="width:auto; margin-bottom:16px; border:1px solid #ccc;">
    <tr style="background:#1a1a2e; color:#fff;">
        <th style="padding:5px 12px; text-align:center;">Total Invoices</th>
        <th style="padding:5px 12px; text-align:center;">Total Amount</th>
        <th style="padding:5px 12px; text-align:center;">Total GST</th>
        <th style="padding:5px 12px; text-align:center;">Net (excl. GST)</th>
    </tr>
    <tr style="font-weight:bold; font-size:13px;">
        <td style="padding:6px 12px; text-align:center;">{{ $totals['count'] }}</td>
        <td style="padding:6px 12px; text-align:right;">{{ number_format($totals['purchase_amount'], 2) }}</td>
        <td style="padding:6px 12px; text-align:right; color:#c07800;">{{ number_format($totals['gst_amount'], 2) }}</td>
        <td style="padding:6px 12px; text-align:right; color:#166534;">{{ number_format($totals['net_amount'], 2) }}</td>
    </tr>
</table>

{{-- Detailed Report --}}
<div style="font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; border-bottom:1px solid #000; padding-bottom:3px;">
    Detailed Report
</div>

<table>
    <thead>
        <tr style="background:#343a40; color:#fff;">
            <th style="width:30px;">No.</th>
            <th>Invoice No.</th>
            <th>Date</th>
            <th>Supplier Name</th>
            <th class="text-right">Invoice Amount</th>
            <th class="text-center">GST Rate</th>
            <th class="text-right">GST Amount</th>
            <th class="text-right">Net (excl. GST)</th>
            <th class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $i => $row)
        @php $net = $row->purchase_amount - $row->gst_amount; @endphp
        <tr style="{{ $i % 2 === 0 ? '' : 'background:#f9f9f9;' }}">
            <td>{{ $i + 1 }}</td>
            <td style="font-weight:600;">{{ $row->invoice_number }}</td>
            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($row->purchase_date)->format('d M Y') }}</td>
            <td>{{ $row->supplier_name }}</td>
            <td class="text-right">{{ number_format($row->purchase_amount, 2) }}</td>
            <td class="text-center">
                @if($row->gst_rate > 0)
                    <span class="badge bg-info" style="color:#000;">{{ number_format($row->gst_rate, 0) }}%</span>
                @else
                    <span style="color:#999;">—</span>
                @endif
            </td>
            <td class="text-right" style="color:{{ $row->gst_amount > 0 ? '#c07800' : '#999' }}; font-weight:{{ $row->gst_amount > 0 ? 'bold' : 'normal' }};">
                {{ number_format($row->gst_amount, 2) }}
            </td>
            <td class="text-right" style="color:#166534;">{{ number_format($net, 2) }}</td>
            <td class="text-center">
                @if($row->payment_status === 'paid')
                    <span class="badge bg-success">Paid</span>
                @elseif($row->payment_status === 'partial')
                    <span class="badge bg-warning">Partial</span>
                @else
                    <span class="badge bg-danger">Unpaid</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center; padding:20px; color:#999;">No records found.</td></tr>
        @endforelse
    </tbody>
    @if($records->count() > 0)
    <tfoot>
        <tr style="background:#1a1a2e; color:#fff; font-weight:bold;">
            <td colspan="4" class="text-right">GRAND TOTAL ({{ $totals['count'] }} invoices)</td>
            <td class="text-right">{{ number_format($totals['purchase_amount'], 2) }}</td>
            <td></td>
            <td class="text-right" style="color:#fbbf24;">{{ number_format($totals['gst_amount'], 2) }}</td>
            <td class="text-right" style="color:#86efac;">{{ number_format($totals['net_amount'], 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Month-wise Summary --}}
@if($monthSummary->count() > 0)
<div style="margin-top:24px; page-break-inside:avoid;">
    <div style="font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; border-bottom:1px solid #000; padding-bottom:3px;">
        Month-wise GST Summary
    </div>
    <table>
        <thead>
            <tr style="background:#343a40; color:#fff;">
                <th style="width:30px;">No.</th>
                <th>Month</th>
                <th class="text-center">Total Invoices</th>
                <th class="text-right">Total Purchase Amount</th>
                <th class="text-right">Total GST Amount</th>
                <th class="text-right">Net Amount (excl. GST)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthSummary as $i => $row)
            @php $net = $row->total_amount - $row->total_gst; @endphp
            <tr style="{{ $i % 2 === 0 ? '' : 'background:#f9f9f9;' }}">
                <td>{{ $i + 1 }}</td>
                <td style="font-weight:600;">
                    {{ \DateTime::createFromFormat('!m', $row->mo)->format('F') }} {{ $row->yr }}
                </td>
                <td class="text-center">{{ $row->total_invoices }}</td>
                <td class="text-right">{{ number_format($row->total_amount, 2) }}</td>
                <td class="text-right" style="color:#c07800; font-weight:bold;">{{ number_format($row->total_gst, 2) }}</td>
                <td class="text-right" style="color:#166534;">{{ number_format($net, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#1a1a2e; color:#fff; font-weight:bold;">
                <td colspan="2" class="text-right">GRAND TOTAL</td>
                <td class="text-center">{{ $monthSummary->sum('total_invoices') }}</td>
                <td class="text-right">{{ number_format($monthSummary->sum('total_amount'), 2) }}</td>
                <td class="text-right" style="color:#fbbf24;">{{ number_format($monthSummary->sum('total_gst'), 2) }}</td>
                <td class="text-right" style="color:#86efac;">
                    {{ number_format($monthSummary->sum('total_amount') - $monthSummary->sum('total_gst'), 2) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

@endsection
