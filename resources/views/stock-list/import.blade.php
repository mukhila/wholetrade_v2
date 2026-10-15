@extends('layouts.app')

@section('content')

<div class="page-title-head d-flex align-items-center mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Import Stock</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Whole Trade</a></li>
            <li class="breadcrumb-item"><a href="{{ route('stock-list.index') }}">Stock List</a></li>
            <li class="breadcrumb-item active">Import</li>
        </ol>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i data-lucide="upload" class="me-2" style="width:18px;height:18px;"></i>
                    Upload CSV File
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('stock-list.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control @error('csv_file') is-invalid @enderror"
                               accept=".csv,.txt" required>
                        @error('csv_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Max file size: 5MB. Accepted formats: .csv, .txt</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-upload me-1"></i> Import
                        </button>
                        <a href="{{ route('stock-list.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i data-lucide="info" class="me-2" style="width:18px;height:18px;"></i>
                    CSV Format Guide
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Your CSV file must have the following <strong>3 columns</strong> in order:
                </p>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Column</th>
                                <th>Field</th>
                                <th>Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>A</code></td>
                                <td>SKU</td>
                                <td><span class="badge bg-danger">Yes</span></td>
                            </tr>
                            <tr>
                                <td><code>B</code></td>
                                <td>Qty (Box)</td>
                                <td><span class="badge bg-secondary">Optional</span></td>
                            </tr>
                            <tr>
                                <td><code>C</code></td>
                                <td>Qty (Loose/PCS)</td>
                                <td><span class="badge bg-secondary">Optional</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="fw-semibold mb-1">Example:</p>
                <pre class="bg-light rounded p-2 mb-3" style="font-size:11px;">SKU,Qty Box,Qty PCS
SKU-000001,10,50
SKU-000002,5,0
SKU-000003,,25</pre>

                <div class="alert alert-info py-2 mb-0" style="font-size:0.82rem;">
                    <i class="ti ti-info-circle me-1"></i>
                    <strong>Note:</strong> The first row (header) is skipped automatically. Empty quantity cells are ignored (existing values kept).
                </div>

                {{-- Download template --}}
                <div class="mt-3">
                    <a href="data:text/csv;charset=utf-8,SKU%2CQty%20Box%2CQty%20PCS%0ASKU-000001%2C10%2C50"
                       download="stock_import_template.csv"
                       class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-download me-1"></i> Download Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
