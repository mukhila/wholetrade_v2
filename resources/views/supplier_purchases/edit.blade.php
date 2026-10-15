@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Purchase — {{ $purchase->invoice_number }}</h4>
            <a href="{{ route('supplier-purchases.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                @if($purchase->paid_amount > 0)
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-1"></i>
                    This invoice has <strong>{{ number_format($purchase->paid_amount, 2) }}</strong> already paid.
                    Purchase amount cannot be reduced below the paid amount.
                </div>
                @endif

                <form method="POST" action="{{ route('supplier-purchases.update', $purchase->id) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">— Select Supplier —</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}"
                                    {{ old('supplier_id', $purchase->supplier_id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control"
                            value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_number" class="form-control"
                            value="{{ old('invoice_number', $purchase->invoice_number) }}"
                            style="text-transform:uppercase;" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purchase Amount (incl. GST) <span class="text-danger">*</span></label>
                        <input type="number" name="purchase_amount" id="purchase_amount" class="form-control"
                            value="{{ old('purchase_amount', $purchase->purchase_amount) }}"
                            step="0.01" min="{{ $purchase->paid_amount }}" required>
                        @if($purchase->paid_amount > 0)
                        <small class="text-muted">Minimum: {{ number_format($purchase->paid_amount, 2) }} (paid amount)</small>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">GST Rate (%)</label>
                            <input type="number" name="gst_rate" id="gst_rate" class="form-control"
                                value="{{ old('gst_rate', $purchase->gst_rate) }}"
                                step="0.01" min="0" max="100" placeholder="e.g. 9">
                            <small class="text-muted">0 = No GST</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label">GST Amount</label>
                            <input type="text" id="gst_amount_display" class="form-control bg-light" readonly
                                value="{{ number_format($purchase->gst_amount, 2) }}">
                            <small class="text-muted">Auto-calculated</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $purchase->remarks) }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Update Purchase
                        </button>
                        <a href="{{ route('supplier-purchases.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function calcGst() {
    const amt  = parseFloat(document.getElementById('purchase_amount').value) || 0;
    const rate = parseFloat(document.getElementById('gst_rate').value) || 0;
    const gst  = rate > 0 ? (amt * rate / (100 + rate)) : 0;
    document.getElementById('gst_amount_display').value = gst.toFixed(2);
}
document.getElementById('purchase_amount').addEventListener('input', calcGst);
document.getElementById('gst_rate').addEventListener('input', calcGst);
</script>
@endpush
@endsection
