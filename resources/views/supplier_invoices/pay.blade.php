@extends('layouts.app')

@section('content')
@php
    $returnUrl = request('return_url');
    if (! $returnUrl || ! str_starts_with($returnUrl, url('/'))) {
        $returnUrl = route('supplier-invoices.index');
    }
    $showUrl = route('supplier-invoices.show', ['id' => $invoice->id, 'return_url' => $returnUrl]);
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Record Payment — {{ $invoice->invoice_number }}</h4>
            <a href="{{ $showUrl }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Invoice
            </a>
        </div>
    </div>
</div>

<div class="row">
    {{-- Invoice summary --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Invoice Summary</h5></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><th class="text-muted small">Invoice #</th><td class="fw-bold text-primary">{{ $invoice->invoice_number }}</td></tr>
                    <tr><th class="text-muted small">Supplier</th><td>{{ $invoice->supplier->name ?? 'N/A' }}</td></tr>
                    <tr><th class="text-muted small">Invoice Date</th><td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td></tr>
                    <tr><th class="text-muted small">Net Total</th><td class="fw-bold">{{ $invoice->currency->symbol ?? '$' }} {{ number_format($invoice->net_total, 2) }}</td></tr>
                    <tr><th class="text-muted small">Paid</th><td class="text-success fw-bold">{{ number_format($invoice->paid_amount, 2) }}</td></tr>
                </table>
                <div class="p-3 rounded text-center" style="background:#dc3545; color:#fff;">
                    <div class="small" style="opacity:.8;">Outstanding Balance</div>
                    <div class="fs-3 fw-bold">{{ $invoice->currency->symbol ?? '$' }} {{ number_format($invoice->balance_due, 2) }}</div>
                </div>
            </div>
        </div>

        @if($paymentHistory && $paymentHistory->count() > 0)
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Payment History</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Mode</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @foreach($paymentHistory as $p)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}</td>
                            <td>{{ $p->payment_mode }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($p->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Payment form --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Payment Details</h5></div>
            <div class="card-body">
                <form id="paymentForm">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ $returnUrl }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Number</label>
                            <input type="text" name="payment_number" class="form-control" value="{{ $rcpNumber }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $invoice->currency->symbol ?? '$' }}</span>
                                <input type="number" name="amount" id="amountInput" class="form-control"
                                    step="0.01" min="0.01" max="{{ $invoice->balance_due }}"
                                    value="{{ $invoice->balance_due }}" required>
                            </div>
                            <small class="text-muted">Max: {{ number_format($invoice->balance_due, 2) }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option>Bank Transfer</option>
                                <option>Cash</option>
                                <option>Cheque</option>
                                <option>PayNow</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference / Cheque #</label>
                            <input type="text" name="reference" class="form-control" placeholder="Reference number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency</label>
                            <select name="currency_id" class="form-select">
                                @foreach($currencies as $c)
                                <option value="{{ $c->id }}" {{ $invoice->currency_id == $c->id ? 'selected' : '' }}>
                                    {{ $c->code }} — {{ $c->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2"
                                placeholder="Notes...">Payment for {{ $invoice->invoice_number }}</textarea>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-muted small">Invoice Total</div>
                                <div class="fw-bold">{{ number_format($invoice->net_total, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Previously Paid</div>
                                <div class="fw-bold text-success">{{ number_format($invoice->paid_amount, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Remaining After</div>
                                <div class="fw-bold" id="balanceAfter">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                            <i class="ti ti-check me-1"></i> Record Payment
                        </button>
                        <a href="{{ $showUrl }}" class="btn btn-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const amountInput  = document.getElementById('amountInput');
    const balanceAfter = document.getElementById('balanceAfter');
    const balanceDue   = {{ $invoice->balance_due }};

    function update() {
        const rem = balanceDue - (parseFloat(amountInput.value) || 0);
        balanceAfter.textContent = rem.toFixed(2);
        balanceAfter.className   = 'fw-bold ' + (rem <= 0 ? 'text-success' : 'text-danger');
    }
    amountInput.addEventListener('input', update);
    update();

    document.getElementById('paymentForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader me-1"></i> Processing...';

        fetch('{{ route("supplier-invoices.pay.store", $invoice->id) }}', {
            method: 'POST',
            body: new FormData(this),
            headers: { 'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { window.location.href = data.redirect; }
            else { alert('Error: ' + (data.error || 'Unknown error')); btn.disabled = false; btn.innerHTML = '<i class="ti ti-check me-1"></i> Record Payment'; }
        })
        .catch(() => { alert('Request failed.'); btn.disabled = false; btn.innerHTML = '<i class="ti ti-check me-1"></i> Record Payment'; });
    });
});
</script>
@endpush
