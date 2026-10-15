@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')

<div class="page-title-head d-flex align-items-center">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Edit Alias: {{ $alias->alias_name }}</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('product-aliases.index') }}">Product Aliases</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('product-aliases.update', $alias->id) }}">
                    @csrf @method('PUT')

                    @php
                        $selectedProductId   = old('product_id', $alias->product_id);
                        $selectedProductText = old('product_text',
                            $alias->product
                                ? $alias->product->name . ($alias->product->sku ? ' (' . $alias->product->sku . ')' : '')
                                : ''
                        );
                    @endphp

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id"
                                class="form-select @error('product_id') is-invalid @enderror" required>
                            @if($selectedProductId)
                                <option value="{{ $selectedProductId }}" selected>{{ $selectedProductText }}</option>
                            @endif
                        </select>
                        @error('product_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Type product name or SKU to search.</small>
                    </div>

                    <input type="hidden" name="product_text" id="product_text" value="{{ $selectedProductText }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alias Name <span class="text-danger">*</span></label>
                        <input type="text" name="alias_name"
                               class="form-control @error('alias_name') is-invalid @enderror"
                               value="{{ old('alias_name', $alias->alias_name) }}"
                               required>
                        @error('alias_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alias SKU</label>
                        <input type="text" name="alias_sku"
                               class="form-control @error('alias_sku') is-invalid @enderror"
                               value="{{ old('alias_sku', $alias->alias_sku) }}"
                               placeholder="Optional">
                        @error('alias_sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="2"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Optional">{{ old('description', $alias->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="status" id="status"
                                   {{ old('status', $alias->status) ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">Active</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Alias</button>
                        <a href="{{ route('product-aliases.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('#product_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Type product name or SKU…',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '{{ route('product-aliases.search-products') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    $('#product_id').on('select2:select', function (e) {
        $('#product_text').val(e.params.data.text);
    });
    $('#product_id').on('select2:clear', function () {
        $('#product_text').val('');
    });
});
</script>
@endpush
