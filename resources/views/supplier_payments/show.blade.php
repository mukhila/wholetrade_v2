@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Payment — {{ $payment->payment_number }}</h4>
            <a href="{{ route('supplier-payments.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Payment Summary</h5></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted small w-40">Payment No.</th><td class="fw-bold text-primary">{{ $payment->payment_number }}</td></tr>
                    <tr><th class="text-muted small">Supplier</th><td>{{ $payment->supplier->name ?? '—' }}</td></tr>
                    <tr><th class="text-muted small">Payment Date</th><td>{{ $payment->payment_date->format('d M Y') }}</td></tr>
                    <tr><th class="text-muted small">Mode</th>
                        <td>
                            <span class="badge {{ $payment->payment_mode === 'GIRO' ? 'bg-info' : ($payment->payment_mode === 'Online Transfer' ? 'bg-primary' : 'bg-secondary') }}">
                                {{ $payment->payment_mode }}
                            </span>
                        </td>
                    </tr>
                    @if($payment->reference_number)
                    <tr><th class="text-muted small">Reference</th><td>{{ $payment->reference_number }}</td></tr>
                    @endif
                    @if($payment->payment_mode === 'GIRO')
                    <tr><th class="text-muted small">GIRO Date</th><td>{{ $payment->giro_date?->format('d M Y') ?? '—' }}</td></tr>
                    <tr><th class="text-muted small">GIRO Status</th>
                        <td>
                            @if($payment->giro_is_paid)
                                <span class="badge bg-success">Paid</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                                @if($canEdit)
                                <form method="POST" action="{{ route('supplier-payments.giro-paid', $payment->id) }}" class="d-inline ms-2">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-sm btn-success py-0 px-2"
                                        onclick="return confirm('Mark this GIRO payment as paid?')">
                                        Mark as Paid
                                    </button>
                                </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if($payment->remarks)
                    <tr><th class="text-muted small">Remarks</th><td>{{ $payment->remarks }}</td></tr>
                    @endif
                    <tr>
                        <th class="text-muted small">Total Amount</th>
                        <td class="fw-bold fs-5 text-success">{{ number_format($payment->total_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Invoices Paid</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Invoice No.</th>
                            <th>Invoice Amount</th>
                            <th class="text-end">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payment->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $item->purchase->invoice_number ?? '—' }}</td>
                            <td>{{ number_format($item->purchase->purchase_amount ?? 0, 2) }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($item->amount_paid, 2) }}</td>
                        </tr>
                        @endforeach
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold text-success">{{ number_format($payment->total_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
