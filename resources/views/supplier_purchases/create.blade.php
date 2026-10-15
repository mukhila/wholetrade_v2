@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Add Purchase</h4>
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

                <form method="POST" action="{{ route('supplier-purchases.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">— Select Supplier —</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control"
                            value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_number" class="form-control"
                            value="{{ old('invoice_number') }}"
                            placeholder="e.g. INV-2024-001" required style="text-transform:uppercase;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purchase Amount (incl. GST) <span class="text-danger">*</span></label>
                        <input type="number" name="purchase_amount" id="purchase_amount" class="form-control"
                            value="{{ old('purchase_amount') }}"
                            step="0.01" min="0.01" placeholder="0.00" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">GST Rate (%)</label>
                            <input type="number" name="gst_rate" id="gst_rate" class="form-control"
                                value="{{ old('gst_rate', 0) }}"
                                step="0.01" min="0" max="100" placeholder="e.g. 9">
                            <small class="text-muted">0 = No GST</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label">GST Amount</label>
                            <input type="text" id="gst_amount_display" class="form-control bg-light" readonly placeholder="0.00">
                            <small class="text-muted">Auto-calculated</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"
                            placeholder="Optional notes">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Save Purchase
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
calcGst();
</script>
@endpush
@endsection
