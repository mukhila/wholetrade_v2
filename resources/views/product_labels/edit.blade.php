@extends('layouts.app')

@section('content')

		<div class="page-title-head d-flex align-items-center ">
            <div class="flex-grow-1">
                <h4 class="fs-xl fw-bold m-0">Edit Label</h4>
            </div>

            <div class="text-end">
                <ol class="breadcrumb m-0 py-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('product-labels.index') }}">Labels</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('product-labels.update', $productLabel->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="label_name" class="form-label">Label Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="label_name" name="label_name" value="{{ old('label_name', $productLabel->label_name) }}" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="width" class="form-label">Width <span class="text-danger">*</span></label>
                                    <input type="number" step="0.1" class="form-control" id="width" name="width" value="{{ old('width', $productLabel->width) }}" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="height" class="form-label">Height <span class="text-danger">*</span></label>
                                    <input type="number" step="0.1" class="form-control" id="height" name="height" value="{{ old('height', $productLabel->height) }}" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
                                    <select class="form-select" id="unit" name="unit" required>
                                        <option value="mm" {{ old('unit', $productLabel->unit) == 'mm' ? 'selected' : '' }}>mm</option>
                                        <option value="cm" {{ old('unit', $productLabel->unit) == 'cm' ? 'selected' : '' }}>cm</option>
                                        <option value="inch" {{ old('unit', $productLabel->unit) == 'inch' ? 'selected' : '' }}>inch</option>
                                    </select>
                                </div>
                            </div>
                            
                            <h5 class="mb-3">Content Configuration</h5>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                     <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_product_name" name="show_product_name" value="1" {{ old('show_product_name', $productLabel->show_product_name) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_product_name">Product Name</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                     <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_sku" name="show_sku" value="1" {{ old('show_sku', $productLabel->show_sku) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_sku">SKU</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                     <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_barcode" name="show_barcode" value="1" {{ old('show_barcode', $productLabel->show_barcode) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_barcode">Barcode</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                     <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_mrp" name="show_mrp" value="1" {{ old('show_mrp', $productLabel->show_mrp) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_mrp">MRP / Price</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                     <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_batch_no" name="show_batch_no" value="1" {{ old('show_batch_no', $productLabel->show_batch_no) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_batch_no">Batch No</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                     <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="expiry_required" name="expiry_required" value="1" {{ old('expiry_required', $productLabel->expiry_required) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="expiry_required">Expiry Required</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                     <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_logo" name="show_logo" value="1" {{ old('show_logo', $productLabel->show_logo) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_logo">Logo</label>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>

                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', $productLabel->status) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status">Active Status</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Label</button>
                            <a href="{{ route('product-labels.index') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
@endsection
