@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Invoice Details</h4>
            <div class="page-title-right">
                <a href="{{ route('sales-invoices.index') }}" class="btn btn-secondary me-2">Back</a>
                <a href="{{ route('sales-invoices.print', $invoice->id) }}" target="_blank" class="btn btn-info me-2"><i class="ti ti-printer"></i> Print</a>
                <a href="{{ route('sales-invoices.thermal-print', $invoice->id) }}" target="_blank" class="btn btn-outline-secondary me-2"><i class="ti ti-receipt"></i> Thermal Print</a>
                @if($invoice->status === 'draft')
                <a href="{{ route('sales-invoices.edit', $invoice->id) }}" class="btn btn-warning me-2">
                    <i class="ti ti-edit me-1"></i> Edit
                </a>
                <button type="button" class="btn btn-primary me-2" id="confirmInvoiceBtn">
                    <i class="ti ti-check me-1"></i> Confirm Invoice
                </button>
                @endif
                @if(in_array($invoice->status, ['confirmed', 'posted']) && $invoice->balance_due > 0)
                <a href="{{ route('sales-invoices.pay', $invoice->id) }}" class="btn btn-success">
                    <i class="ti ti-cash me-1"></i> Record Payment
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Header -->
                <!-- Company Header -->
                <div class="row align-items-center mb-5 border-bottom pb-4">
                    @include('partials.company-header')
                    <div class="col-4 text-end">
                        <div class="fw-bold text-primary mb-2">TAX INVOICE</div>
                        <h5 class="fw-bold mb-0">#{{ $invoice->invoice_number }}</h5>
                        <div class="text-muted">Date: {{ $invoice->invoice_date }}</div>
                        <div class="text-muted">Due Date: {{ $invoice->due_date ?? '-' }}</div>
                        <div class="mt-2 text-end">
                            <span class="badge bg-{{ $invoice->payment_status == 'paid' ? 'success' : ($invoice->payment_status == 'partial' ? 'warning' : 'danger') }} fs-6">
                                {{ strtoupper($invoice->payment_status) }}
                            </span>
                        </div>
                    </div>
                    @if($invoice->balance_due > 0)
                    <div class="col-12 text-end mt-2">
                         <h5 class="text-danger fw-bold">Balance Due: {{ number_format($invoice->balance_due, 2) }}</h5>
                    </div>
                    @endif
                </div>

                <!-- Customer Outstanding Balance Alert -->
                @if(isset($customerOutstandingBalance) && $customerOutstandingBalance > 0)
                <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong>Customer Total Outstanding Balance:</strong>
                    </div>
                    <div class="fs-4 fw-bold text-danger">
                        {{ $invoice->currency->symbol ?? '$' }}{{ number_format($customerOutstandingBalance, 2) }}
                    </div>
                </div>
                @endif

                <!-- Addresses -->
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="fw-bold">Bill To:</h6>
                        <address>
                            <strong>{{ $invoice->customer->name ?? 'N/A' }}</strong><br>
                            {!! nl2br(e($invoice->billing_address)) !!}
                        </address>
                        <p><strong>Salesperson:</strong> {{ $invoice->salesperson->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-6">
                        <h6 class="fw-bold">Ship To:</h6>
                        <address>
                            {!! nl2br(e($invoice->shipping_address)) !!}
                        </address>
                         @if($invoice->reference_number)
                        <p><strong>Ref #:</strong> {{ $invoice->reference_number }}</p>
                        @endif
                    </div>
                </div>

                <!-- Items -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Disc</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->product->sku ?? '' }}</strong> - {{ $item->product->name ?? $item->item_description }}
                                    @if($item->item_description && $item->item_description !== ($item->product->name ?? ''))
                                        <div class="small text-muted">{{ $item->item_description }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ number_format($item->total_qty, 0) }}
                                    <small class="text-muted d-block">{{ $item->box_qty > 0 ? "({$item->box_qty} Box)" : '' }}</small>
                                </td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format($item->discount_amount, 2) }}</td>
                                <td class="text-end">{{ number_format($item->tax_amount, 2) }}</td>
                                <td class="text-end">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">Subtotal</th>
                                <th class="text-end">{{ number_format($invoice->subtotal, 2) }}</th>
                            </tr>
                            @if($invoice->discount_amount > 0)
                            <tr>
                                <th colspan="6" class="text-end">Discount</th>
                                <th class="text-end text-danger">-{{ number_format($invoice->discount_amount, 2) }}</th>
                            </tr>
                            @endif
                            @if($invoice->tax_amount > 0)
                            <tr>
                                <th colspan="6" class="text-end">Tax</th>
                                <th class="text-end">{{ number_format($invoice->tax_amount, 2) }}</th>
                            </tr>
                            @endif
                            <tr>
                                <th colspan="6" class="text-end fs-5">Net Total ({{ $invoice->currency->symbol ?? '$' }})</th>
                                <th class="text-end fs-5">{{ number_format($invoice->net_total, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="6" class="text-end">Paid Amount</th>
                                <th class="text-end text-success">{{ number_format($invoice->paid_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($invoice->status === 'draft')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmInvoiceBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to confirm this invoice?\n\nThis action will:\n- Lock the invoice from editing\n- Deduct inventory for all items\n\nThis cannot be undone.')) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="ti ti-loader me-1"></i> Confirming...';

                fetch('{{ route("sales-invoices.confirm", $invoice->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="ti ti-check me-1"></i> Confirm Invoice';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error confirming invoice. Please try again.');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="ti ti-check me-1"></i> Confirm Invoice';
                });
            }
        });
    }
});
</script>
@endpush
@endif
