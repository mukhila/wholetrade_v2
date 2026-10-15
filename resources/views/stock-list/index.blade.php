@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .summary-card { border-left: 4px solid; }
        .summary-card.cost  { border-color: #0d6efd; }
        .summary-card.sell  { border-color: #198754; }
        .table th { font-size: 0.8rem; white-space: nowrap; }
        .table td { font-size: 0.85rem; vertical-align: middle; }
        .price-tag { font-weight: 600; color: #0d6efd; }
        .sell-tag  { font-weight: 600; color: #198754; }
        .qty-badge { font-size: 0.8rem; }
        th a.sort-link { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; }
        th a.sort-link:hover { color: #0d6efd; }
        th a.sort-link .sort-icon { opacity: .35; font-size: .7rem; }
        th a.sort-link.active .sort-icon { opacity: 1; color: #0d6efd; }
    </style>
@endpush

@section('content')

<div class="page-title-head d-flex align-items-center mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Stock List</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Whole Trade</a></li>
            <li class="breadcrumb-item">Master Data</li>
            <li class="breadcrumb-item active">Stock List</li>
        </ol>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        @if(session('import_errors'))
            <ul class="mt-2 mb-0">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    {{-- Total Products --}}
    <div class="col-md-4 col-sm-6">
        <div class="card summary-card cost h-100 mb-0">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                        <i data-lucide="package" class="text-primary" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0" style="font-size:.75rem;">Total Products</p>
                        <h5 class="fw-bold mb-0">{{ number_format($products->total()) }}</h5>
                        <small class="text-muted" style="font-size:.7rem;">All matching</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Qty Loose --}}
    <div class="col-md-4 col-sm-6">
        <div class="card summary-card h-100 mb-0" style="border-left-color:#6f42c1;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="background:rgba(111,66,193,.12);">
                        <i data-lucide="box" style="width:20px;height:20px;color:#6f42c1;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0" style="font-size:.75rem;">Total Stock (Loose/PCS)</p>
                        <h5 class="fw-bold mb-0" style="color:#6f42c1;">{{ number_format($totalQtyPcs) }}</h5>
                        <small class="text-muted" style="font-size:.7rem;">Box: {{ number_format($totalQtyBox) }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Grand Total Cost --}}
    <div class="col-md-4 col-sm-6">
        <div class="card summary-card cost h-100 mb-0">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                        <i data-lucide="dollar-sign" class="text-primary" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0" style="font-size:.75rem;">Grand Total Cost Value</p>
                        <h5 class="fw-bold mb-0 text-primary">{{ number_format($totalCostValue, 2) }}</h5>
                        <small class="text-muted" style="font-size:.7rem;">All matched products</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">

        {{-- Filters --}}
        <form method="GET" action="{{ route('stock-list.index') }}" class="row g-2 mb-3" id="filterForm">
            <div class="col-md-3">
                <select class="form-select" name="category_id" id="categoryFilter">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="brand_id" id="brandFilter">
                    <option value="">All Brands</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" name="search"
                           placeholder="Search by Name or SKU..."
                           value="{{ request('search') }}">
                    @if(request()->anyFilled(['search', 'category_id', 'brand_id']))
                        <a href="{{ route('stock-list.index') }}" class="btn btn-outline-secondary" title="Clear">
                            <i class="ti ti-x"></i>
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="ti ti-search me-1"></i> Filter
                </button>
            </div>
        </form>

        {{-- Action Buttons --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('stock-list.print', request()->query()) }}" target="_blank"
               class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-printer me-1"></i> Print
            </a>
            @if($canExport)
            <a href="{{ route('stock-list.export', request()->query()) }}"
               class="btn btn-outline-success btn-sm">
                <i class="ti ti-file-spreadsheet me-1"></i> Export CSV
            </a>
            @endif
            @if($canImport)
            <a href="{{ route('stock-list.import.form') }}"
               class="btn btn-outline-primary btn-sm">
                <i class="ti ti-upload me-1"></i> Import Stock
            </a>
            @endif
            @if($canCreate)
            <a href="{{ route('stock-list.add-stock.form') }}"
               class="btn btn-success btn-sm">
                <i class="ti ti-circle-plus me-1"></i> Add Stock
            </a>
            @endif
        </div>

        {{-- Table --}}
        @php
            $sortBy  = request('sort_by', 'name');
            $sortDir = request('sort_dir', 'asc');

            $sortLink = function(string $col, string $label, string $align = 'left') use ($sortBy, $sortDir): string {
                $isActive  = $sortBy === $col;
                $newDir    = ($isActive && $sortDir === 'asc') ? 'desc' : 'asc';
                $params    = array_merge(request()->query(), ['sort_by' => $col, 'sort_dir' => $newDir]);
                $url       = route('stock-list.index', $params);
                $iconClass = $isActive
                    ? ($sortDir === 'asc' ? 'ti ti-sort-ascending' : 'ti ti-sort-descending')
                    : 'ti ti-arrows-sort';
                $activeClass  = $isActive ? ' active' : '';
                $justifyClass = $align === 'end' ? 'justify-content-end' : ($align === 'center' ? 'justify-content-center' : '');
                return '<a href="' . e($url) . '" class="sort-link' . $activeClass . ' ' . $justifyClass . '">'
                    . e($label)
                    . '<i class="sort-icon ' . $iconClass . '"></i>'
                    . '</a>';
            };
        @endphp

        <div class="table-responsive" id="print-area">
            <table class="table table-centered table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th style="width:52px;"></th>
                        <th>{!! $sortLink('sku', 'SKU') !!}</th>
                        <th>{!! $sortLink('name', 'Product Name') !!}</th>
                        <th class="text-end">{!! $sortLink('cost_item', 'Cost/Item', 'end') !!}</th>
                        <th class="text-end">{!! $sortLink('sell_item', 'Sell/Item', 'end') !!}</th>
                        <th class="text-center">{!! $sortLink('stock_qty_box', 'Qty (Box)', 'center') !!}</th>
                        <th class="text-center">{!! $sortLink('stock_qty_pcs', 'Qty (Loose)', 'center') !!}</th>
                        <th class="text-end">{!! $sortLink('total_cost', 'Total Cost', 'end') !!}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $i => $product)
                        @php
                            $qtyPcs    = max(0, $product->stock_qty_pcs ?? 0);
                            $qtyBox    = max(0, $product->stock_qty_box ?? 0);
                            $costItem  = $product->price?->cost_price_item  ?? 0;
                            $costBox   = $product->price?->cost_price_box   ?? 0;
                            $sellItem  = $product->price?->sales_price_item ?? 0;
                            $totalCost = ($qtyPcs * $costItem) + ($qtyBox * $costBox);
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $products->firstItem() + $i }}</td>
                            <td>
                                @if($product->image)
                                    <img src="{{ \Storage::disk('public_uploads')->url($product->image->image_path) }}"
                                         alt="{{ $product->name }}"
                                         style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6;">
                                @else
                                    <div style="width:40px;height:40px;border-radius:4px;border:1px solid #dee2e6;background:#f8f9fa;display:flex;align-items:center;justify-content:center;">
                                        <i class="ti ti-photo text-muted" style="font-size:1rem;"></i>
                                    </div>
                                @endif
                            </td>
                            <td><code>{{ $product->sku }}</code></td>
                            <td class="fw-medium">{{ $product->name }}</td>
                            <td class="text-end price-tag">{{ number_format($costItem, 2) }}</td>
                            <td class="text-end sell-tag">{{ number_format($sellItem, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-info qty-badge">{{ number_format($qtyBox, 0) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary qty-badge">{{ number_format($qtyPcs, 0) }}</span>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($totalCost, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i data-lucide="inbox" style="width:32px;height:32px;" class="mb-2 d-block mx-auto"></i>
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if($products->count() > 0)
                @php
                    $pageQtyPcs = $products->sum(fn($p) => max(0, $p->stock_qty_pcs ?? 0));
                    $pageQtyBox = $products->sum(fn($p) => max(0, $p->stock_qty_box ?? 0));
                    $pageCost   = $products->sum(fn($p) =>
                        max(0, $p->stock_qty_pcs ?? 0) * ($p->price?->cost_price_item ?? 0) +
                        max(0, $p->stock_qty_box ?? 0) * ($p->price?->cost_price_box  ?? 0)
                    );
                @endphp
                <tfoot>
                    {{-- Page subtotal --}}
                    <tr class="table-light">
                        <td colspan="6" class="text-end text-muted" style="font-size:.8rem;">
                            Page Subtotal ({{ $products->count() }} of {{ $products->total() }} products):
                        </td>
                        <td class="text-center fw-semibold" style="font-size:.8rem;color:#0dcaf0;">
                            {{ number_format($pageQtyBox, 0) }}
                        </td>
                        <td class="text-center fw-semibold" style="font-size:.8rem;color:#6f42c1;">
                            {{ number_format($pageQtyPcs, 0) }}
                        </td>
                        <td class="text-end text-muted fw-semibold" style="font-size:.8rem;">
                            {{ number_format($pageCost, 2) }}
                        </td>
                    </tr>
                    {{-- Grand total — ALL filtered products --}}
                    <tr style="background:#1a1a2e; color:#fff;">
                        <td colspan="6" class="text-end fw-bold" style="font-size:.85rem; letter-spacing:.5px;">
                            GRAND TOTAL &mdash; ALL {{ number_format($products->total()) }} PRODUCTS:
                        </td>
                        <td class="text-center fw-bold" style="font-size:.9rem; color:#67e8f9;">
                            {{ number_format($totalQtyBox, 0) }}
                        </td>
                        <td class="text-center fw-bold" style="font-size:.9rem; color:#c4b5fd;">
                            {{ number_format($totalQtyPcs, 0) }}
                        </td>
                        <td class="text-end fw-bold" style="font-size:.9rem; color:#7eb8ff;">
                            {{ number_format($totalCostValue, 2) }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">
                Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} products
            </small>
            {{ $products->links() }}
        </div>

    </div>
</div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#categoryFilter').select2({ theme: 'bootstrap-5', placeholder: 'All Categories', allowClear: true, width: '100%' });
            $('#brandFilter').select2({ theme: 'bootstrap-5', placeholder: 'All Brands', allowClear: true, width: '100%' });
        });
    </script>
@endpush
