@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
    .table th { font-size: 0.8rem; white-space: nowrap; }
    .table td { font-size: 0.85rem; vertical-align: middle; }

    /* Read-only columns */
    .col-readonly { background: #f8f9fa; color: #555; }

    /* Add Qty input */
    .qty-input {
        text-align: right;
        font-weight: 600;
        min-width: 100px;
        border: 1.5px solid #dee2e6;
        transition: border-color .15s, background .15s;
    }
    .qty-input:focus  { border-color: #198754; background: #f0fff4; outline: none; box-shadow: none; }
    .qty-input.has-qty { border-color: #198754 !important; background: #f0fff4 !important; }

    /* New total cell highlight */
    .new-total-cell { font-weight: 700; color: #0d6efd; }
    .new-total-cell.increased { color: #198754; }

    /* Sticky save bar */
    .save-bar {
        position: sticky;
        bottom: 0;
        z-index: 100;
        background: #fff;
        border-top: 2px solid #198754;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        box-shadow: 0 -2px 8px rgba(0,0,0,.08);
    }

    /* Cost price column */
    .cost-tag { font-weight: 600; color: #0d6efd; }
</style>
@endpush

@section('content')

<div class="page-title-head d-flex align-items-center mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Add Stock</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Whole Trade</a></li>
            <li class="breadcrumb-item"><a href="{{ route('stock-list.index') }}">Stock List</a></li>
            <li class="breadcrumb-item active">Add Stock</li>
        </ol>
    </div>
</div>

{{-- Error Alert --}}
@if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-circle me-1"></i> {{ $errors->first('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="alert alert-success mb-3" role="alert">
    <i class="ti ti-info-circle me-1"></i>
    <strong>Bulk Add Stock:</strong>
    Enter the quantity to <strong>add</strong> for each product in the <em>Add Qty</em> column.
    The <em>New Total</em> and <em>New Total Cost</em> update live. Leave a row blank (or 0) to skip it.
    Only rows with a quantity &gt; 0 are saved.
</div>

<div class="card">
    <div class="card-body pb-0">

        {{-- Filters --}}
        <form method="GET" action="{{ route('stock-list.add-stock.form') }}" class="row g-2 mb-3">
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
                    @if(request()->anyFilled(['search', 'category_id', 'brand_id']))
                        <a href="{{ route('stock-list.add-stock.form') }}" class="btn btn-outline-secondary btn-sm" title="Clear">
                            <i class="ti ti-x"></i>
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="ti ti-search me-1"></i> Filter
                </button>
                <a href="{{ route('stock-list.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left me-1"></i> Back
                </a>
            </div>
        </form>

        {{-- Stats bar --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">
                Showing {{ $products->firstItem() }}–{{ $products->lastItem() }}
                of {{ $products->total() }} products
                &nbsp;·&nbsp; Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
            </small>
            <small class="text-muted">
                <span id="modifiedCount" class="fw-bold text-success">0</span> row(s) with qty to add
            </small>
        </div>

        {{-- Main Form --}}
        <form method="POST" action="{{ route('stock-list.add-stock') }}" id="addStockForm">
            @csrf

            {{-- Preserve filters for redirect --}}
            @foreach(request()->only(['category_id','brand_id','search','page']) as $key => $val)
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endforeach

            <div class="table-responsive">
                <table class="table table-centered table-hover table-sm mb-0" id="addStockTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th class="text-end col-readonly">Cost/Item</th>
                            <th class="text-center col-readonly">Current Qty (Loose)</th>
                            <th class="text-center" style="background:#e8f9f0; min-width:120px;">
                                <i class="ti ti-circle-plus me-1 text-success" style="font-size:.75rem;"></i>Add Qty
                            </th>
                            <th class="text-center" style="background:#eef4ff;">New Total Qty</th>
                            <th class="text-end" style="background:#eef4ff;">New Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $i => $product)
                            @php
                                $currentQty = max(0, $product->stock_qty_pcs ?? 0);
                                $costItem   = $product->price?->cost_price_item ?? 0;
                                $totalCost  = $currentQty * $costItem;
                            @endphp
                            <tr data-product-id="{{ $product->id }}"
                                data-current="{{ $currentQty }}"
                                data-cost="{{ $costItem }}">
                                <td class="text-muted">{{ $products->firstItem() + $i }}</td>
                                <td><code class="text-primary">{{ $product->sku }}</code></td>
                                <td>
                                    <div class="fw-medium">{{ $product->name }}</div>
                                    <small class="text-muted" style="font-size:.72rem;">
                                        {{ $product->category?->name ?? '-' }}
                                        @if($product->brand?->name)
                                            &nbsp;·&nbsp;{{ $product->brand->name }}
                                        @endif
                                    </small>
                                </td>
                                <td class="text-end col-readonly cost-tag">
                                    {{ number_format($costItem, 2) }}
                                </td>
                                <td class="text-center col-readonly">
                                    <span class="badge bg-secondary" style="font-size:.8rem;">
                                        {{ number_format($currentQty, 0) }}
                                    </span>
                                </td>
                                <td class="text-center" style="background:#f0fff8;">
                                    <input type="number"
                                           name="add_qty[{{ $product->id }}]"
                                           class="form-control form-control-sm qty-input"
                                           step="1"
                                           min="0"
                                           value=""
                                           placeholder="0"
                                           autocomplete="off">
                                </td>
                                <td class="text-center new-total-cell" data-base="{{ $currentQty }}">
                                    <span class="badge bg-primary" style="font-size:.8rem;">
                                        {{ number_format($currentQty, 0) }}
                                    </span>
                                </td>
                                <td class="text-end new-cost-cell fw-semibold text-muted">
                                    {{ number_format($totalCost, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i data-lucide="inbox" style="width:36px;height:36px;" class="mb-2 d-block mx-auto"></i>
                                    No products found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Page summary footer --}}
                    @if($products->count() > 0)
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end text-muted" style="font-size:.8rem;">
                                Page Total ({{ $products->count() }} products):
                            </td>
                            <td class="text-center fw-semibold" style="font-size:.8rem;color:#6f42c1;" id="footCurrentTotal">
                                {{ number_format($products->sum(fn($p) => max(0, $p->stock_qty_pcs ?? 0)), 0) }}
                            </td>
                            <td class="text-center fw-semibold text-success" style="font-size:.8rem;" id="footAddTotal">0</td>
                            <td class="text-center fw-bold" style="font-size:.8rem;color:#0d6efd;" id="footNewTotal">
                                {{ number_format($products->sum(fn($p) => max(0, $p->stock_qty_pcs ?? 0)), 0) }}
                            </td>
                            <td class="text-end fw-semibold" style="font-size:.8rem;" id="footNewCost">
                                {{ number_format($products->sum(fn($p) => max(0, $p->stock_qty_pcs ?? 0) * ($p->price?->cost_price_item ?? 0)), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            {{-- Sticky Save Bar --}}
            @if($products->count() > 0)
            <div class="save-bar">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-muted" style="font-size:.83rem;">
                        <span id="modifiedCountBar" class="fw-bold text-success">0</span> product(s) to update
                    </span>
                    <span class="text-muted" style="font-size:.83rem;">
                        Adding: <strong id="totalAddingBar" class="text-success">0</strong> units
                        &nbsp;·&nbsp;
                        New cost total: <strong id="newCostBar" class="text-primary">{{ number_format($products->sum(fn($p) => max(0, $p->stock_qty_pcs ?? 0) * ($p->price?->cost_price_item ?? 0)), 2) }}</strong>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClear">
                        <i class="ti ti-eraser me-1"></i> Clear All
                    </button>
                    <button type="submit" class="btn btn-success btn-sm px-4" id="btnSave" disabled>
                        <i class="ti ti-circle-plus me-1"></i> Add Stock
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    $('#categoryFilter').select2({ theme: 'bootstrap-5', placeholder: 'All Categories', allowClear: true, width: '100%' });
    $('#brandFilter').select2({ theme: 'bootstrap-5', placeholder: 'All Brands',      allowClear: true, width: '100%' });
});

(function () {
    var inputs       = document.querySelectorAll('.qty-input');
    var btnSave      = document.getElementById('btnSave');
    var btnClear     = document.getElementById('btnClear');
    var modifiedEl   = document.getElementById('modifiedCount');
    var modifiedBarEl= document.getElementById('modifiedCountBar');
    var totalAddBar  = document.getElementById('totalAddingBar');
    var newCostBar   = document.getElementById('newCostBar');
    var footAddTotal = document.getElementById('footAddTotal');
    var footNewTotal = document.getElementById('footNewTotal');
    var footNewCost  = document.getElementById('footNewCost');

    function fmt(n, decimals) {
        return n.toLocaleString('en-MY', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function recalc() {
        var modified   = 0;
        var totalAdding = 0;
        var totalNewCost = 0;
        var pageCurrentTotal = 0;

        inputs.forEach(function (inp) {
            var row      = inp.closest('tr');
            var current  = parseFloat(row.dataset.current) || 0;
            var cost     = parseFloat(row.dataset.cost)    || 0;
            var addQty   = parseFloat(inp.value)           || 0;

            // Clamp to 0
            if (addQty < 0) {
                inp.value = 0;
                addQty = 0;
            }

            var newTotal  = current + addQty;
            var newCost   = newTotal * cost;
            var hasQty    = addQty > 0;

            // Update input state
            inp.classList.toggle('has-qty', hasQty);
            row.style.background = hasQty ? 'rgba(25,135,84,.04)' : '';

            // Update New Total cell
            var newTotalCell = row.querySelector('.new-total-cell');
            var badge = newTotalCell.querySelector('.badge');
            if (badge) {
                badge.textContent = fmt(newTotal, 0);
                badge.className   = 'badge ' + (hasQty ? 'bg-success' : 'bg-primary');
            }
            newTotalCell.classList.toggle('increased', hasQty);

            // Update New Total Cost cell
            var costCell = row.querySelector('.new-cost-cell');
            costCell.textContent   = fmt(newCost, 2);
            costCell.style.color   = hasQty ? '#198754' : '';
            costCell.style.fontWeight = hasQty ? '700' : '600';

            // Accumulate
            if (hasQty) modified++;
            totalAdding  += addQty;
            totalNewCost += newCost;
            pageCurrentTotal += current;
        });

        // Update counters
        if (modifiedEl)    modifiedEl.textContent    = modified;
        if (modifiedBarEl) modifiedBarEl.textContent = modified;
        if (totalAddBar)   totalAddBar.textContent   = fmt(totalAdding, 0);
        if (newCostBar)    newCostBar.textContent     = fmt(totalNewCost, 2);
        if (btnSave)       btnSave.disabled           = modified === 0;

        // Update footer
        if (footAddTotal) footAddTotal.textContent = fmt(totalAdding, 0);
        if (footNewTotal) footNewTotal.textContent = fmt(pageCurrentTotal + totalAdding, 0);
        if (footNewCost)  footNewCost.textContent  = fmt(totalNewCost, 2);
    }

    inputs.forEach(function (inp) {
        inp.addEventListener('input',  recalc);
        inp.addEventListener('change', recalc);
    });

    if (btnClear) {
        btnClear.addEventListener('click', function () {
            if (!confirm('Clear all Add Qty entries on this page?')) return;
            inputs.forEach(function (inp) {
                inp.value = '';
                inp.classList.remove('has-qty');
                inp.closest('tr').style.background = '';
            });
            recalc();
        });
    }

    // Warn on unsaved changes — but NOT when the form is being submitted intentionally
    var isSubmitting = false;

    document.getElementById('addStockForm').addEventListener('submit', function () {
        isSubmitting = true;
    });

    window.addEventListener('beforeunload', function (e) {
        if (!isSubmitting && btnSave && !btnSave.disabled) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    recalc();
})();
</script>
@endpush
