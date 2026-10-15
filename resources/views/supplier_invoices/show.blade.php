@extends('layouts.app')

@section('content')
@php
    $returnUrl = request('return_url');
    if (! $returnUrl || ! str_starts_with($returnUrl, url('/'))) {
        $returnUrl = route('supplier-invoices.index');
    }
    $returnParams = ['return_url' => $returnUrl];
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Supplier Invoice — {{ $invoice->invoice_number }}</h4>
            <div class="d-flex gap-2">
                <a href="{{ $returnUrl }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back
                </a>
                <a href="{{ route('supplier-invoices.print', $invoice->id) }}" target="_blank" class="btn btn-info">
                    <i class="ti ti-printer me-1"></i> Print
                </a>
                @if($invoice->canEdit() && $canEdit)
                    <a href="{{ route('supplier-invoices.edit', array_merge(['id' => $invoice->id], $returnParams)) }}" class="btn btn-warning">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                    <button id="confirmBtn" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i> Confirm & Add Stock
                    </button>
                @endif
                @if($invoice->canPay())
                    <a href="{{ route('supplier-invoices.pay', array_merge(['id' => $invoice->id], $returnParams)) }}" class="btn btn-success">
                        <i class="ti ti-cash me-1"></i> Record Payment
                    </a>
                @endif
                @if($invoice->status === 'draft' && $canDelete)
                <form method="POST" action="{{ route('supplier-invoices.destroy', $invoice->id) }}"
                      onsubmit="return confirm('Delete this draft invoice?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="ti ti-trash me-1"></i> Delete
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                {{-- Header --}}
                <div class="row align-items-start mb-4 border-bottom pb-4">
                    <div class="col-8">
                        <h5 class="fw-bold text-uppercase text-muted mb-1">Supplier Invoice</h5>
                        <h3 class="fw-bold text-primary">#{{ $invoice->invoice_number }}</h3>
                        <div class="mt-3">
                            <strong class="d-block">{{ $invoice->supplier->name ?? 'N/A' }}</strong>
                            <span class="text-muted small">{{ $invoice->supplier->code ?? '' }}</span>
                            @if($invoice->billing_address)
                                <p class="text-muted small mb-0 mt-1">{!! nl2br(e($invoice->billing_address)) !!}</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="mb-2">
                            @if($invoice->status === 'confirmed')
                                <span class="badge bg-success fs-6">Confirmed</span>
                            @elseif($invoice->status === 'cancelled')
                                <span class="badge bg-secondary fs-6">Cancelled</span>
                            @else
                                <span class="badge bg-warning text-dark fs-6">Draft</span>
                            @endif
                            &nbsp;
                            @if($invoice->payment_status === 'paid')
                                <span class="badge bg-success fs-6">PAID</span>
                            @elseif($invoice->payment_status === 'partial')
                                <span class="badge bg-info fs-6">PARTIAL</span>
                            @else
                                <span class="badge bg-danger fs-6">UNPAID</span>
                            @endif
                        </div>
                        <table class="table table-sm table-borderless text-end mb-0">
                            <tr><th class="text-muted small">Invoice Date</th><td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td></tr>
                            <tr><th class="text-muted small">Due Date</th><td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : '-' }}</td></tr>
                            @if($invoice->supplier_ref_number)
                            <tr><th class="text-muted small">Supplier Ref</th><td>{{ $invoice->supplier_ref_number }}</td></tr>
                            @endif
                        </table>
                        @if($invoice->balance_due > 0)
                        <div class="mt-2 p-2 bg-danger bg-opacity-10 rounded border border-danger">
                            <div class="text-muted small">Balance Due</div>
                            <div class="fs-4 fw-bold text-danger">
                                {{ $invoice->currency->symbol ?? '$' }} {{ number_format($invoice->balance_due, 2) }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Stock notice --}}
                @if($invoice->stock_added)
                <div class="alert alert-success d-flex align-items-center mb-4">
                    <i class="ti ti-circle-check me-2 fs-4"></i>
                    <div><strong>Stock Added:</strong> All products from this invoice have been added to inventory.</div>
                </div>
                @endif

                {{-- Items table --}}
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        @php $showGst = isset($companySettings) && $companySettings->gst_include === 'Yes'; @endphp
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-center">Box Qty</th>
                                <th class="text-center">Pcs Qty</th>
                                <th class="text-center">Total Qty</th>
                                <th class="text-end">Unit Price</th>
                                @if($showGst)
                                <th class="text-end">GST %</th>
                                <th class="text-end">GST Amt</th>
                                @endif
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $item->product->sku ?? '' }}</strong> — {{ $item->product->name ?? $item->item_description }}
                                    @if($item->item_description && $item->item_description !== ($item->product->name ?? ''))
                                        <div class="small text-muted">{{ $item->item_description }}</div>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->box_qty }}</td>
                                <td class="text-center">{{ $item->item_qty }}</td>
                                <td class="text-center">{{ $item->total_qty }}</td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                @if($showGst)
                                <td class="text-end">{{ $item->tax_rate }}%</td>
                                <td class="text-end">{{ number_format($item->tax_amount, 2) }}</td>
                                @endif
                                <td class="text-end fw-bold">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="{{ $showGst ? 8 : 6 }}" class="text-end">Subtotal</th>
                                <th class="text-end">{{ number_format($invoice->subtotal, 2) }}</th>
                            </tr>
                            @if($invoice->discount_amount > 0)
                            <tr>
                                <th colspan="{{ $showGst ? 8 : 6 }}" class="text-end">Discount</th>
                                <th class="text-end text-danger">-{{ number_format($invoice->discount_amount, 2) }}</th>
                            </tr>
                            @endif
                            @if($showGst && $invoice->tax_amount > 0)
                            <tr>
                                <th colspan="8" class="text-end">GST</th>
                                <th class="text-end">{{ number_format($invoice->tax_amount, 2) }}</th>
                            </tr>
                            @endif
                            <tr>
                                <th colspan="{{ $showGst ? 8 : 6 }}" class="text-end fs-5">Net Total ({{ $invoice->currency->symbol ?? '$' }})</th>
                                <th class="text-end fs-5 fw-bold">{{ number_format($invoice->net_total, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="{{ $showGst ? 8 : 6 }}" class="text-end">Paid</th>
                                <th class="text-end text-success">{{ number_format($invoice->paid_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($invoice->remarks)
                <div class="alert alert-light border"><strong>Remarks:</strong> {{ $invoice->remarks }}</div>
                @endif

                {{-- Payment history --}}
                @if($paymentHistory && $paymentHistory->count() > 0)
                <h6 class="fw-bold mt-4">Payment History</h6>
                <table class="table table-sm table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Payment #</th>
                            <th>Date</th>
                            <th>Mode</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentHistory as $p)
                        <tr>
                            <td>{{ $p->payment_number ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}</td>
                            <td>{{ $p->payment_mode }}</td>
                            <td>{{ $p->reference ?? '-' }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($p->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection

@if($invoice->canConfirm())
@push('scripts')
<script>
document.getElementById('confirmBtn')?.addEventListener('click', function () {
    if (!confirm('Confirm this invoice?\n\nThis will:\n• Lock the invoice from editing\n• Add all products to stock inventory\n\nThis cannot be undone.')) return;

    this.disabled = true;
    this.innerHTML = '<i class="ti ti-loader me-1"></i> Confirming...';

    fetch('{{ route("supplier-invoices.confirm", $invoice->id) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { alert(data.message); location.reload(); }
        else { alert('Error: ' + (data.error || 'Unknown error')); this.disabled = false; this.innerHTML = '<i class="ti ti-check me-1"></i> Confirm & Add Stock'; }
    })
    .catch(() => { alert('Request failed. Please try again.'); this.disabled = false; this.innerHTML = '<i class="ti ti-check me-1"></i> Confirm & Add Stock'; });
});
</script>
@endpush
@endif
