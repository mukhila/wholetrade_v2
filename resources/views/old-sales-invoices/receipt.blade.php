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
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Make Receipt for Old Invoice</h4>
            <div class="page-title-right">
                <a href="{{ route('old-sales-invoices.index') }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to List
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
                            <div class="value text-primary">{{ $invoice->invoice_no }}</div>
                        </div>
                        <div class="col-6">
                            <div class="label">Invoice Date</div>
                            <div class="value">{{ $invoice->date ? $invoice->date->format('d M Y') : '-' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="label">Customer</div>
                            <div class="value">{{ $invoice->customer_name ?? ($invoice->customer->name ?? 'N/A') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="label">Net Total</div>
                            <div class="value">{{ number_format($invoice->net_total, 2) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="label">Already Paid</div>
                            <div class="value text-success">{{ number_format($invoice->paid, 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="balance-highlight">
                    <div class="label">Outstanding Balance</div>
                    <div class="value">{{ number_format($invoice->balance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Receipt Information</h5>
            </div>
            <div class="card-body">
                <form id="receiptForm">
                    @csrf
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
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="amountInput" class="form-control" step="0.01" min="0.01" max="{{ $invoice->balance }}" value="{{ $invoice->balance }}" required>
                            <small class="text-muted">Max: {{ number_format($invoice->balance, 2) }}</small>
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
                                <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference / Cheque #</label>
                            <input type="text" name="reference" class="form-control" placeholder="Enter reference number">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes...">Payment for old invoice: {{ $invoice->invoice_no }}</textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                            <i class="ti ti-check me-1"></i> Create Receipt
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="submitAndPrintBtn">
                            <i class="ti ti-printer me-1"></i> Create & Print Invoice
                        </button>
                        <a href="{{ route('old-sales-invoices.index') }}" class="btn btn-secondary btn-lg">
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
                <h4>Receipt Created Successfully!</h4>
                <p class="text-muted mb-4">The payment has been recorded.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('old-sales-invoices.index') }}" class="btn btn-primary">
                        Back to List
                    </a>
                    <a href="#" id="printInvoiceBtn" class="btn btn-info" target="_blank">
                        <i class="ti ti-printer me-1"></i> Print Invoice
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
    const form = document.getElementById('receiptForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitAndPrintBtn = document.getElementById('submitAndPrintBtn');
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    let shouldPrint = false;

    function handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData(form);
        submitBtn.disabled = true;
        submitAndPrintBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader me-1"></i> Processing...';

        fetch('{{ route("old-sales-invoices.receipt.store", $invoice->id) }}', {
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
                document.getElementById('printInvoiceBtn').href = '{{ route("old-sales-invoices.print", $invoice->id) }}';

                if (shouldPrint) {
                    // Open print window directly
                    window.open('{{ route("old-sales-invoices.print", $invoice->id) }}', '_blank');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                } else {
                    successModal.show();
                }
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
                submitBtn.disabled = false;
                submitAndPrintBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ti ti-check me-1"></i> Create Receipt';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitAndPrintBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-check me-1"></i> Create Receipt';
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
