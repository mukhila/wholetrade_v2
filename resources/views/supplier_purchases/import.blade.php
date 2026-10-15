@extends('layouts.app')

@section('content')
<div class="row mt-2">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Import Supplier Purchases</h4>
            <a href="{{ route('supplier-purchases.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">

        @if(session('import_success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="ti ti-circle-check me-1"></i> {{ session('import_success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('import_skipped') && count(session('import_skipped')))
            <div class="alert alert-warning alert-dismissible fade show">
                <strong><i class="ti ti-alert-triangle me-1"></i> The following rows were skipped:</strong>
                <ul class="mb-0 mt-2">
                    @foreach(session('import_skipped') as $msg)
                        <li>{{ $msg }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Instructions --}}
        <div class="card mb-3">
            <div class="card-header bg-light py-2">
                <strong><i class="ti ti-info-circle me-1"></i> Instructions</strong>
            </div>
            <div class="card-body">
                <ol class="mb-2">
                    <li>Download the <a href="{{ route('supplier-purchases.sample-csv') }}"><i class="ti ti-download me-1"></i>Sample CSV template</a>.</li>
                    <li>Fill in your purchase data. <strong>Supplier Name</strong> must exactly match a supplier in the system.</li>
                    <li>Use <code>YYYY-MM-DD</code> format for dates (e.g. <code>2024-01-15</code>).</li>
                    <li>Set <strong>GST Rate (%)</strong> to <code>0</code> if there is no GST.</li>
                    <li>Upload the completed file below.</li>
                </ol>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Column</th>
                                <th>Required</th>
                                <th>Example</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Supplier Name</td><td><span class="badge bg-danger">Yes</span></td><td>ABC Supplier Pte Ltd</td></tr>
                            <tr><td>Invoice Number</td><td><span class="badge bg-danger">Yes</span></td><td>INV-2024-001</td></tr>
                            <tr><td>Purchase Date</td><td><span class="badge bg-danger">Yes</span></td><td>2024-01-15</td></tr>
                            <tr><td>Purchase Amount</td><td><span class="badge bg-danger">Yes</span></td><td>1000.00</td></tr>
                            <tr><td>GST Rate (%)</td><td><span class="badge bg-secondary">No</span></td><td>9 or 0</td></tr>
                            <tr><td>Remarks</td><td><span class="badge bg-secondary">No</span></td><td>Monthly supply</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Upload form --}}
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('supplier-purchases.import.process') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <small class="text-muted">Accepted: .csv — max 2 MB</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-upload me-1"></i> Import
                        </button>
                        <a href="{{ route('supplier-purchases.sample-csv') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-download me-1"></i> Download Sample
                        </a>
                        <a href="{{ route('supplier-purchases.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
