@extends('layouts.app')

@push('styles')
<style>
    .invoice-summary {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .invoice-summary .label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .invoice-summary .value {
        font-size: 18px;
        font-weight: bold;
        color: #212529;
    }
    .balance-highlight {
        background: #dc3545;
        color: #fff;
        padding: 15px 25px;
        border-radius: 10px;
        text-align: center;
    }
    .balance-highlight .label {
        color: rgba(255,255,255,0.8);
    }
    .balance-highlight .value {
        color: #fff;
        font-size: 28px;
    }
    .payment-history-table {
        max-height: 200px;
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
@php
    $returnUrl = request('return_url');
    if (! $returnUrl || ! str_starts_with($returnUrl, url('/'))) {
        $returnUrl = route('sales-invoices.index');
    }
    $showUrl = route('sales-invoices.show', ['sales_invoice' => $invoice->id, 'return_url' => $returnUrl]);
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Record Payment</h4>
            <div class="page-title-right">
                <a href="{{ $showUrl }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Invoice
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Invoice Summary -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Invoice Details</h5>
            </div>
            <div class="card-body">
                <div class="invoice-summary">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="label">Invoice Number</div>
                            <div class="value text-primary">{{ $invoice->invoice_number }}</div>
                        </div>
                        <div class="col-6">
                            <div class="label">Invoice Date</div>
                            <div class="value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="label">Customer</div>
                            <div class="value">{{ $invoice->customer->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="label">Net Total</div>
                            <div class="value">{{ $invoice->currency->symbol ?? 'SGD' }} {{ number_format($invoice->net_total, 2) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="label">Already Paid</div>
                            <div class="value text-success">{{ number_format($invoice->paid_amount, 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="balance-highlight">
                    <div class="label">Outstanding Balance</div>
                    <div class="value">{{ $invoice->currency->symbol ?? 'SGD' }} {{ number_format($invoice->balance_due, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        @if($paymentHistory && $paymentHistory->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment History</h5>
            </div>
            <div class="card-body p-0">
                <div class="payment-history-table">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentHistory as $payment)
                            <tr>
                                <td>{{ $payment->receipt_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($payment->receipt_date)->format('d M Y') }}</td>
                                <td class="text-end text-success fw-bold">{{ number_format($payment->pivot->allocated_amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Payment Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment Information</h5>
            </div>
            <div class="card-body">
                <form id="paymentForm">
                    @csrf
                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                    <input type="hidden" name="customer_id" value="{{ $invoice->customer_id }}">
                    <input type="hidden" name="return_url" value="{{ $returnUrl }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Receipt Number <span class="text-danger">*</span></label>
                            <input type="text" name="receipt_number" class="form-control" value="{{ $rcpNumber }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $invoice->currency->symbol ?? 'SGD' }}</span>
                                <input type="number" name="amount" id="amountInput" class="form-control" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required>
                            </div>
                            <small class="text-muted">Max: {{ number_format($invoice->balance_due, 2) }}</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="PayNow">PayNow</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency</label>
                            <select name="currency_id" class="form-select">
                                @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ ($invoice->currency_id == $currency->id) ? 'selected' : '' }}>
                                    {{ $currency->code }} - {{ $currency->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference / Cheque #</label>
                            <input type="text" name="reference" class="form-control" placeholder="Enter reference number">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes...">Payment for Invoice: {{ $invoice->invoice_number }}</textarea>
                        </div>
                    </div>

                    <!-- Payment Allocation Preview -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="mb-3">Payment Allocation Preview</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-muted small">Invoice Total</div>
                                <div class="fw-bold">{{ number_format($invoice->net_total, 2) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Previously Paid</div>
                                <div class="fw-bold text-success">{{ number_format($invoice->paid_amount, 2) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">After This Payment</div>
                                <div class="fw-bold" id="balanceAfter">{{ number_format(0, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div id="paymentError" class="alert alert-danger d-none mb-3" role="alert">
                        <i class="ti ti-alert-circle me-1"></i>
                        <span id="paymentErrorMsg"></span>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                            <i class="ti ti-check me-1"></i> Record Payment
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="submitAndPrintBtn">
                            <i class="ti ti-printer me-1"></i> Record & Print Receipt
                        </button>
                        <a href="{{ $showUrl }}" class="btn btn-secondary btn-lg">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <i class="ti ti-circle-check text-success" style="font-size: 64px;"></i>
                </div>
                <h4>Payment Recorded Successfully!</h4>
                <p class="text-muted mb-4">The payment has been applied to the invoice.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ $returnUrl }}" class="btn btn-primary">
                        Back to Invoices
                    </a>
                    <a href="#" id="printReceiptBtn" class="btn btn-info" target="_blank">
                        <i class="ti ti-printer me-1"></i> Print Receipt
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('paymentForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitAndPrintBtn = document.getElementById('submitAndPrintBtn');
    const amountInput = document.getElementById('amountInput');
    const balanceAfter = document.getElementById('balanceAfter');
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));

    const balanceDue = {{ $invoice->balance_due }};
    let shouldPrint = false;
    let receiptId = null;

    // Update balance preview
    function updateBalancePreview() {
        const amount = parseFloat(amountInput.value) || 0;
        const remaining = balanceDue - amount;
        balanceAfter.textContent = remaining.toFixed(2);
        balanceAfter.className = remaining <= 0 ? 'fw-bold text-success' : 'fw-bold text-danger';
    }

    amountInput.addEventListener('input', updateBalancePreview);
    updateBalancePreview(); // Initial

    function handleSubmit(e) {
        e.preventDefault();

        // Hide any previous error
        document.getElementById('paymentError').classList.add('d-none');

        const formData = new FormData(form);
        submitBtn.disabled = true;
        submitAndPrintBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader me-1"></i> Processing...';

        fetch('{{ route("sales-invoices.pay.store", $invoice->id) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                receiptId = data.receipt_id;
                document.getElementById('printReceiptBtn').href = '/sales-receipts/' + receiptId + '/print';

                if (shouldPrint) {
                    window.open('/sales-receipts/' + receiptId + '/print', '_blank');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                } else {
                    successModal.show();
                }
            } else {
                const errorDiv = document.getElementById('paymentError');
                document.getElementById('paymentErrorMsg').textContent = data.error || 'An error occurred. Please try again.';
                errorDiv.classList.remove('d-none');
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                submitBtn.disabled = false;
                submitAndPrintBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ti ti-check me-1"></i> Record Payment';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorDiv = document.getElementById('paymentError');
            document.getElementById('paymentErrorMsg').textContent = 'An error occurred. Please try again.';
            errorDiv.classList.remove('d-none');
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            submitBtn.disabled = false;
            submitAndPrintBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-check me-1"></i> Record Payment';
        });
    }

    form.addEventListener('submit', function(e) {
        shouldPrint = false;
        handleSubmit(e);
    });

    submitAndPrintBtn.addEventListener('click', function(e) {
        shouldPrint = true;
        handleSubmit(e);
    });
});
</script>
@endpush
