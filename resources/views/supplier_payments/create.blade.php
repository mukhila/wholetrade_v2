@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Add Payment</h4>
            <a href="{{ route('supplier-payments.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('supplier-payments.store') }}" id="paymentForm">
@csrf
<div class="row">
    {{-- Left: Supplier + Invoice Selection --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Step 1 — Select Supplier & Invoices</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="supplierSelect" class="form-select" required>
                        <option value="">— Select Supplier —</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="invoiceSection" style="display:none;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label mb-0">Due Invoices <span class="text-danger">*</span></label>
                        <small class="text-muted" id="invoiceCount"></small>
                    </div>

                    <div id="invoiceLoadingSpinner" class="text-center py-3" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        <span class="ms-2 text-muted small">Loading invoices...</span>
                    </div>

                    <div id="noInvoicesMsg" class="alert alert-info py-2" style="display:none;">
                        No outstanding invoices for this supplier.
                    </div>

                    <div class="table-responsive" id="invoiceTable" style="display:none;">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:40px;">
                                        <input type="checkbox" id="selectAll" title="Select All">
                                    </th>
                                    <th>Invoice No.</th>
                                    <th>Date</th>
                                    <th class="text-end">Invoice Amt</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-end" style="width:130px;">Pay Amount</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Payment Details --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Step 2 — Payment Details</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control"
                        value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_mode" id="modeCash"
                                value="Cash" checked>
                            <label class="form-check-label" for="modeCash">Cash</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_mode" id="modeOnline"
                                value="Online Transfer">
                            <label class="form-check-label" for="modeOnline">Online Transfer</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_mode" id="modeGiro"
                                value="GIRO">
                            <label class="form-check-label" for="modeGiro">GIRO</label>
                        </div>
                    </div>
                </div>

                {{-- Online Transfer: Reference Number --}}
                <div id="onlineFields" style="display:none;" class="mb-3">
                    <label class="form-label">Payment Reference Number <span class="text-danger">*</span></label>
                    <input type="text" name="reference_number" id="referenceInput"
                        class="form-control" placeholder="e.g. TRF20240601001">
                </div>

                {{-- GIRO Fields --}}
                <div id="giroFields" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label">GIRO Date <span class="text-danger">*</span></label>
                        <input type="date" name="giro_date" id="giroDateInput" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GIRO Reference Number <span class="text-danger">*</span></label>
                        <input type="text" name="reference_number" id="giroRefInput"
                            class="form-control" placeholder="e.g. GIRO-2024-001">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"
                        placeholder="Optional notes"></textarea>
                </div>

                {{-- Payment Summary --}}
                <div class="p-3 rounded mb-4" style="background:#f8f9fa; border:1px solid #dee2e6;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Invoices Selected</span>
                        <span class="fw-bold" id="summaryCount">0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Total Payment</span>
                        <span class="fw-bold fs-5 text-primary" id="summaryTotal">0.00</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 btn-lg" id="submitBtn">
                    <i class="ti ti-check me-1"></i> Record Payment
                </button>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const supplierSelect = document.getElementById('supplierSelect');
    const invoiceSection = document.getElementById('invoiceSection');
    const invoiceRows    = document.getElementById('invoiceRows');
    const invoiceTable   = document.getElementById('invoiceTable');
    const noInvoicesMsg  = document.getElementById('noInvoicesMsg');
    const spinner        = document.getElementById('invoiceLoadingSpinner');
    const invoiceCount   = document.getElementById('invoiceCount');
    const summaryCount   = document.getElementById('summaryCount');
    const summaryTotal   = document.getElementById('summaryTotal');
    const selectAll      = document.getElementById('selectAll');

    // Load due invoices on supplier change
    supplierSelect.addEventListener('change', function () {
        const id = this.value;
        invoiceSection.style.display = id ? 'block' : 'none';
        if (!id) return;

        spinner.style.display = 'block';
        invoiceTable.style.display = 'none';
        noInvoicesMsg.style.display = 'none';
        invoiceRows.innerHTML = '';

        fetch(`{{ url('supplier-payments/due-invoices') }}?supplier_id=${id}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            spinner.style.display = 'none';
            if (!data.length) {
                noInvoicesMsg.style.display = 'block';
                return;
            }
            invoiceTable.style.display = 'block';
            invoiceCount.textContent = `${data.length} invoice(s) due`;

            data.forEach((inv, i) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">
                        <input type="checkbox" class="invoice-check" data-balance="${inv.balance}" data-idx="${i}">
                    </td>
                    <td class="fw-medium">${inv.invoice_number}</td>
                    <td>${new Date(inv.purchase_date).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'})}</td>
                    <td class="text-end">${parseFloat(inv.purchase_amount).toFixed(2)}</td>
                    <td class="text-end text-danger fw-bold">${parseFloat(inv.balance).toFixed(2)}</td>
                    <td class="text-end">
                        <input type="number" name="invoices[${i}][id]" value="${inv.id}" class="inv-id" hidden>
                        <input type="number" name="invoices[${i}][amount]" class="form-control form-control-sm inv-amount text-end"
                            value="${parseFloat(inv.balance).toFixed(2)}"
                            step="0.01" min="0.01" max="${inv.balance}"
                            style="width:110px;" disabled>
                    </td>
                `;
                invoiceRows.appendChild(row);
            });

            // Checkbox toggle
            document.querySelectorAll('.invoice-check').forEach(cb => {
                cb.addEventListener('change', function () {
                    const idx    = this.dataset.idx;
                    const amtIn  = invoiceRows.querySelectorAll('.inv-amount')[idx];
                    amtIn.disabled = !this.checked;
                    if (!this.checked) amtIn.value = parseFloat(this.dataset.balance).toFixed(2);
                    updateSummary();
                    updateSelectAll();
                });
            });

            document.querySelectorAll('.inv-amount').forEach(inp => {
                inp.addEventListener('input', updateSummary);
            });
        })
        .catch(() => {
            spinner.style.display = 'none';
            noInvoicesMsg.textContent = 'Failed to load invoices.';
            noInvoicesMsg.style.display = 'block';
        });
    });

    // Select All checkbox
    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.invoice-check').forEach((cb, i) => {
            cb.checked = this.checked;
            const amtIn = invoiceRows.querySelectorAll('.inv-amount')[i];
            amtIn.disabled = !this.checked;
        });
        updateSummary();
    });

    function updateSelectAll() {
        const all = document.querySelectorAll('.invoice-check');
        const checked = document.querySelectorAll('.invoice-check:checked');
        selectAll.checked = all.length > 0 && all.length === checked.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
    }

    function updateSummary() {
        let count = 0, total = 0;
        document.querySelectorAll('.invoice-check:checked').forEach((cb, i) => {
            const idx   = cb.dataset.idx;
            const amtIn = invoiceRows.querySelectorAll('.inv-amount')[idx];
            const v     = parseFloat(amtIn.value) || 0;
            if (v > 0) { count++; total += v; }
        });
        summaryCount.textContent = count;
        summaryTotal.textContent = total.toFixed(2);
    }

    // Payment mode toggle
    const modeRadios = document.querySelectorAll('[name="payment_mode"]');
    const onlineFields = document.getElementById('onlineFields');
    const giroFields   = document.getElementById('giroFields');
    const refInput     = document.getElementById('referenceInput');
    const giroDateIn   = document.getElementById('giroDateInput');
    const giroRefIn    = document.getElementById('giroRefInput');

    modeRadios.forEach(r => r.addEventListener('change', toggleModeFields));
    function toggleModeFields() {
        const mode = document.querySelector('[name="payment_mode"]:checked')?.value;
        onlineFields.style.display = mode === 'Online Transfer' ? 'block' : 'none';
        giroFields.style.display   = mode === 'GIRO' ? 'block' : 'none';

        refInput.required   = mode === 'Online Transfer';
        giroDateIn.required = mode === 'GIRO';
        giroRefIn.required  = mode === 'GIRO';
    }

    // Form submit validation
    document.getElementById('paymentForm').addEventListener('submit', function (e) {
        const checked = document.querySelectorAll('.invoice-check:checked');
        if (!checked.length) {
            e.preventDefault();
            alert('Please select at least one invoice to pay.');
            return;
        }

        let valid = true;
        checked.forEach(cb => {
            const idx   = cb.dataset.idx;
            const amtIn = invoiceRows.querySelectorAll('.inv-amount')[idx];
            const max   = parseFloat(amtIn.max);
            const val   = parseFloat(amtIn.value);
            if (!val || val <= 0 || val > max) {
                alert(`Invalid amount for invoice. Must be between 0.01 and ${max.toFixed(2)}.`);
                valid = false;
            }
        });

        if (!valid) { e.preventDefault(); return; }

        // Disable inputs for unchecked rows so they are not included in the POST data
        document.querySelectorAll('.invoice-check').forEach(cb => {
            const row = cb.closest('tr');
            const idIn  = row.querySelector('.inv-id');
            const amtIn = row.querySelector('.inv-amount');
            if (!cb.checked) {
                if (idIn)  idIn.disabled  = true;
                if (amtIn) amtIn.disabled = true;
            } else {
                if (amtIn) amtIn.disabled = false;
            }
        });

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader me-1"></i> Processing...';
    });
});
</script>
@endpush
