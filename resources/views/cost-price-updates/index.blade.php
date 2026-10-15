@extends('layouts.app')

@push('styles')
<style>
    .table th { font-size: 0.8rem; white-space: nowrap; }
    .table td { font-size: 0.85rem; vertical-align: middle; }

    .col-current { background: #f8f9fa; color: #6c757d; }

    .price-input {
        text-align: right;
        font-weight: 600;
        min-width: 120px;
        border: 1.5px solid #dee2e6;
        transition: border-color .15s, background .15s;
    }
    .price-input:focus { border-color: #0d6efd; background: #fff8e1; outline: none; box-shadow: none; }
    .price-input.is-changed { border-color: #198754 !important; background: #f0fff4 !important; }

    .save-bar {
        position: sticky;
        bottom: 0;
        z-index: 100;
        background: #fff;
        border-top: 2px solid #0d6efd;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        box-shadow: 0 -2px 8px rgba(0,0,0,.08);
    }
</style>
@endpush

@section('content')

<div class="page-title-head d-flex align-items-center mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Cost Price Update</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Whole Trade</a></li>
            <li class="breadcrumb-item">Master Data</li>
            <li class="breadcrumb-item active">Cost Price Update</li>
        </ol>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-circle-check me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-circle me-1"></i> {{ $errors->first('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="alert alert-info mb-3" role="alert">
    <i class="ti ti-info-circle me-1"></i>
    <strong>Cost Price Update:</strong>
    Edit the <strong>New Cost/Item</strong> field inline.
    Changed rows highlight green. Click <strong>Save Changes</strong> — only modified rows are saved and audit-logged.
</div>

<div class="card">
    <div class="card-body pb-0">

        {{-- Filters --}}
        <form method="GET" action="{{ route('cost-price-updates.index') }}" class="row g-2 mb-3" id="filterForm">
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="category_id" id="categoryFilter">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="brand_id" id="brandFilter">
                    <option value="">All Brands</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control form-control-sm" name="search"
                           placeholder="Search Name or SKU..."
                           value="{{ request('search') }}">
                    @if(request()->anyFilled(['search','category_id','brand_id']))
                        <a href="{{ route('cost-price-updates.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                            <i class="ti ti-x"></i>
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ti ti-search me-1"></i> Filter
                </button>
            </div>
        </form>

        {{-- Product Count --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">
                Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} products
            </small>
            <small class="text-muted">
                <span id="changedCount" class="fw-bold text-success">0</span> row(s) changed
            </small>
        </div>

        {{-- Main Form --}}
        <form method="POST" action="{{ route('cost-price-updates.update') }}" id="costPriceForm">
            @csrf
            @method('PUT')

            @foreach(request()->only(['category_id','brand_id','search','page']) as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach

            <div class="table-responsive">
                <table class="table table-centered table-hover table-sm mb-0" id="costPriceTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th class="text-end col-current">Current Cost/Item</th>
                            <th class="text-end" style="background:#e8f4fd;">
                                <i class="ti ti-pencil me-1 text-primary" style="font-size:.7rem;"></i>New Cost/Item
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $i => $product)
                            @php
                                $currentCostItem = $product->price?->cost_price_item ?? 0;
                            @endphp
                            <tr data-row="{{ $product->id }}">
                                <td class="text-muted">{{ $products->firstItem() + $i }}</td>
                                <td><code class="text-primary">{{ $product->sku }}</code></td>
                                <td class="fw-medium">{{ $product->name }}</td>
                                <td>{{ $product->category?->name ?? '-' }}</td>
                                <td>{{ $product->brand?->name ?? '-' }}</td>

                                {{-- Current price — read only --}}
                                <td class="text-end col-current fw-semibold">
                                    {{ number_format($currentCostItem, 4) }}
                                </td>

                                {{-- Editable new cost/item --}}
                                <td class="text-end" style="background:#f0f7ff;">
                                    <input type="number"
                                           name="prices[{{ $product->id }}][cost_price_item]"
                                           class="form-control form-control-sm price-input"
                                           step="0.0001"
                                           min="0"
                                           value="{{ old('prices.'.$product->id.'.cost_price_item', number_format($currentCostItem, 4, '.', '')) }}"
                                           data-original="{{ number_format($currentCostItem, 4, '.', '') }}"
                                           autocomplete="off">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i data-lucide="inbox" style="width:36px;height:36px;" class="mb-2 d-block mx-auto"></i>
                                    No products found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Sticky Save Bar --}}
            @if($products->count() > 0 && $canEdit)
            <div class="save-bar">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size:.83rem;">
                        Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
                        &nbsp;·&nbsp;
                        <span id="changedCountBar" class="text-success fw-bold">0</span> unsaved change(s)
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnReset">
                        <i class="ti ti-refresh me-1"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-success btn-sm px-4" id="btnSave" disabled>
                        <i class="ti ti-device-floppy me-1"></i> Save Changes
                    </button>
                </div>
            </div>
            @endif

        </form>

    </div>
</div>

{{-- Pagination --}}
<div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">
        Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}
        of {{ $products->total() }} products
    </small>
    {{ $products->links() }}
</div>

@endsection

@push('scripts')
<script>
(function () {
    var inputs     = document.querySelectorAll('.price-input');
    var countEl    = document.getElementById('changedCount');
    var countBarEl = document.getElementById('changedCountBar');
    var btnSave    = document.getElementById('btnSave');
    var btnReset   = document.getElementById('btnReset');

    function recalcChanged() {
        var changed = 0;
        inputs.forEach(function (inp) {
            var original  = parseFloat(inp.dataset.original) || 0;
            var current   = parseFloat(inp.value)            || 0;
            var isChanged = Math.abs(original - current) > 0.00005;

            inp.classList.toggle('is-changed', isChanged);
            inp.closest('tr').style.background = isChanged ? 'rgba(25,135,84,.05)' : '';

            if (isChanged) changed++;
        });

        if (countEl)    countEl.textContent    = changed;
        if (countBarEl) countBarEl.textContent = changed;
        if (btnSave)    btnSave.disabled       = changed === 0;
    }

    inputs.forEach(function (inp) {
        inp.addEventListener('input',  recalcChanged);
        inp.addEventListener('change', recalcChanged);
    });

    if (btnReset) {
        btnReset.addEventListener('click', function () {
            if (!confirm('Reset all changes on this page?')) return;
            inputs.forEach(function (inp) {
                inp.value = inp.dataset.original;
                inp.classList.remove('is-changed');
                inp.closest('tr').style.background = '';
            });
            if (countEl)    countEl.textContent    = 0;
            if (countBarEl) countBarEl.textContent = 0;
            if (btnSave)    btnSave.disabled       = true;
        });
    }

    window.addEventListener('beforeunload', function (e) {
        if (btnSave && !btnSave.disabled) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    var form = document.getElementById('costPriceForm');
    if (form) {
        form.addEventListener('submit', function () {
            window.onbeforeunload = null;
        });
    }

    recalcChanged();
})();
</script>
@endpush
