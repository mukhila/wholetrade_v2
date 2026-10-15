@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
/* ── Variables ─────────────────────────────────────────── */
:root {
    --pl-primary:   #8fbd48;
    --pl-primary-l: #f0f8e8;
    --pl-accent:    #10b981;
    --pl-danger:    #ef4444;
    --pl-warning:   #f59e0b;
    --pl-surface:   #f8fafc;
    --pl-border:    #e2e8f0;
    --pl-text:      #1e293b;
    --pl-muted:     #64748b;
    --pl-radius:    10px;
    --pl-shadow:    0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --pl-shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
}

/* ── Layout ────────────────────────────────────────────── */
.pl-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.pl-sidebar { width: 230px; flex-shrink: 0; position: sticky; top: 76px; }
.pl-main    { flex: 1; min-width: 0; }

/* ── Page Header ───────────────────────────────────────── */
.pl-header {
    background: linear-gradient(135deg, #0c763d 0%, #08783e 50%, #0a783e 100%);
    border-radius: var(--pl-radius);
    padding: 20px 24px;
    color: #fff;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.pl-header-title { font-size: 1.4rem; font-weight: 700; letter-spacing: -.3px; }
.pl-header-sub   { font-size: .82rem; opacity: .8; margin-top: 2px; }

/* ── Cart FAB ──────────────────────────────────────────── */
.cart-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 1040;
    background: var(--pl-primary);
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 12px 22px;
    font-size: .9rem;
    font-weight: 600;
    box-shadow: 0 4px 20px rgba(143,189,72,.45);
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all .2s;
}
.cart-fab:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(143,189,72,.55); }
.cart-fab .badge {
    background: #ef4444;
    color: #fff;
    border-radius: 50px;
    padding: 1px 7px;
    font-size: .72rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
}

/* ── Filter Card ───────────────────────────────────────── */
.filter-card {
    background: #fff;
    border-radius: var(--pl-radius);
    box-shadow: var(--pl-shadow);
    overflow: hidden;
}
.filter-card-header {
    background: var(--pl-primary);
    color: #fff;
    padding: 12px 16px;
    font-size: .85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}
.filter-card-body { padding: 16px; }
.filter-label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: var(--pl-muted);
    margin-bottom: 8px;
    margin-top: 14px;
}
.filter-label:first-child { margin-top: 0; }
.filter-radio {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .83rem;
    color: var(--pl-text);
    padding: 4px 0;
    cursor: pointer;
}
.filter-radio input { cursor: pointer; accent-color: var(--pl-primary); }
.filter-search {
    width: 100%;
    border: 1px solid var(--pl-border);
    border-radius: 6px;
    padding: 7px 10px;
    font-size: .83rem;
    outline: none;
    color: var(--pl-text);
    transition: border-color .15s;
}
.filter-search:focus { border-color: var(--pl-primary); }
.btn-reset-filters {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--pl-border);
    border-radius: 6px;
    background: var(--pl-surface);
    color: var(--pl-muted);
    font-size: .8rem;
    cursor: pointer;
    margin-top: 14px;
    transition: all .15s;
}
.btn-reset-filters:hover { background: var(--pl-danger); color: #fff; border-color: var(--pl-danger); }

/* ── Toolbar ───────────────────────────────────────────── */
.pl-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
    background: #fff;
    border-radius: var(--pl-radius);
    padding: 10px 16px;
    box-shadow: var(--pl-shadow);
}
.pl-result-count { font-size: .83rem; color: var(--pl-muted); }
.pl-result-count strong { color: var(--pl-text); }
.pl-toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.per-page-select {
    border: 1px solid var(--pl-border);
    border-radius: 6px;
    padding: 5px 8px;
    font-size: .8rem;
    color: var(--pl-text);
    cursor: pointer;
    outline: none;
}
.view-toggle { display: flex; gap: 4px; }
.view-btn {
    border: 1px solid var(--pl-border);
    background: #fff;
    border-radius: 6px;
    padding: 5px 9px;
    cursor: pointer;
    color: var(--pl-muted);
    transition: all .15s;
}
.view-btn.active, .view-btn:hover { background: var(--pl-primary); color: #fff; border-color: var(--pl-primary); }

/* ── Product Grid / List ───────────────────────────────── */
#product-grid { display: grid; gap: 14px; }
#product-grid.grid-view  { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
#product-grid.list-view  { grid-template-columns: 1fr; }

/* ── Product Card ──────────────────────────────────────── */
.product-card {
    background: #fff;
    border-radius: var(--pl-radius);
    box-shadow: var(--pl-shadow);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow .2s, transform .2s;
    position: relative;
}
.product-card:hover { box-shadow: var(--pl-shadow-md); transform: translateY(-2px); }
.product-card.list-view { flex-direction: row; }

.product-img-wrap {
    aspect-ratio: 1;
    overflow: hidden;
    background: var(--pl-surface);
    position: relative;
}
.product-card.list-view .product-img-wrap { width: 110px; flex-shrink: 0; aspect-ratio: 1; }
.product-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .3s;
}
.product-card:hover .product-img-wrap img { transform: scale(1.05); }
.no-img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    font-size: 2.2rem;
}
.product-badge-sku {
    position: absolute;
    top: 6px;
    left: 6px;
    background: rgba(30,41,59,.75);
    color: #fff;
    font-size: .62rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: .3px;
    backdrop-filter: blur(3px);
}
.out-of-stock-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,.72);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    font-weight: 700;
    color: var(--pl-danger);
    letter-spacing: .5px;
}

.product-body { padding: 12px; flex: 1; display: flex; flex-direction: column; gap: 6px; }
.product-name { font-size: .83rem; font-weight: 600; color: var(--pl-text); line-height: 1.3; }
.product-cat  { font-size: .7rem; color: var(--pl-muted); }
.price-row    { display: flex; gap: 8px; flex-wrap: wrap; }
.price-chip {
    display: flex;
    flex-direction: column;
    background: var(--pl-surface);
    border: 1px solid var(--pl-border);
    border-radius: 6px;
    padding: 4px 8px;
    flex: 1;
    min-width: 70px;
}
.price-chip-label { font-size: .62rem; color: var(--pl-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.price-chip-value { font-size: .88rem; font-weight: 700; color: var(--pl-primary); }
.stock-info { font-size: .68rem; color: var(--pl-muted); }
.stock-info.low { color: var(--pl-warning); }
.stock-info.out { color: var(--pl-danger); }

/* ── Add To Cart Controls ──────────────────────────────── */
.add-to-cart-row { display: flex; gap: 6px; align-items: center; margin-top: auto; }
.qty-input {
    width: 54px;
    border: 1px solid var(--pl-border);
    border-radius: 6px;
    padding: 5px 6px;
    font-size: .8rem;
    text-align: center;
    outline: none;
    color: var(--pl-text);
}
.qty-input:focus { border-color: var(--pl-primary); }
.btn-add-cart {
    flex: 1;
    background: var(--pl-primary);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: background .15s;
}
.btn-add-cart:hover   { background: #7aaa30; }
.btn-add-cart.success { background: var(--pl-accent); }
.btn-add-cart.success::after { content: ' ✓'; }
.price-mode-toggle {
    display: flex;
    gap: 4px;
    font-size: .7rem;
}
.pm-btn {
    border: 1px solid var(--pl-border);
    background: #fff;
    border-radius: 4px;
    padding: 2px 6px;
    cursor: pointer;
    color: var(--pl-muted);
    transition: all .1s;
}
.pm-btn.active { background: var(--pl-primary); color: #fff; border-color: var(--pl-primary); }

/* ── Cart Offcanvas ────────────────────────────────────── */
.offcanvas-cart { width: 420px !important; }
@media (max-width: 500px) { .offcanvas-cart { width: 100vw !important; } }
.cart-header {
    background: linear-gradient(135deg, #0c763d, #0a783e);
    color: #fff;
    padding: 16px 20px;
}
.cart-header h5 { margin: 0; font-size: 1rem; font-weight: 700; }
.cart-header .badge { background: #ef4444; font-size: .72rem; }

/* Cart empty state */
.cart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 24px;
    color: var(--pl-muted);
    text-align: center;
}
.cart-empty svg { width: 56px; height: 56px; stroke: #cbd5e1; margin-bottom: 12px; }

/* Cart items */
.cart-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 0;
    border-bottom: 1px solid var(--pl-border);
}
.cart-item-img {
    width: 48px;
    height: 48px;
    border-radius: 6px;
    object-fit: cover;
    background: var(--pl-surface);
    flex-shrink: 0;
}
.cart-item-name  { font-size: .82rem; font-weight: 600; color: var(--pl-text); }
.cart-item-sku   { font-size: .68rem; color: var(--pl-muted); }
.cart-item-price { font-size: .8rem; color: var(--pl-primary); font-weight: 700; }
.cart-item-total { font-size: .8rem; font-weight: 700; color: var(--pl-text); margin-left: auto; white-space: nowrap; }

.cart-qty-control { display: flex; align-items: center; gap: 4px; margin-top: 4px; }
.cart-qty-btn {
    width: 22px;
    height: 22px;
    border: 1px solid var(--pl-border);
    border-radius: 4px;
    background: #fff;
    cursor: pointer;
    font-size: .85rem;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--pl-text);
    transition: all .1s;
}
.cart-qty-btn:hover { background: var(--pl-primary); color: #fff; border-color: var(--pl-primary); }
.cart-qty-input {
    width: 40px;
    text-align: center;
    border: 1px solid var(--pl-border);
    border-radius: 4px;
    font-size: .8rem;
    padding: 1px 4px;
    color: var(--pl-text);
    outline: none;
}
.cart-qty-input:focus { border-color: var(--pl-primary); }
.btn-remove-item {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 4px;
    transition: color .1s, background .1s;
    font-size: 1rem;
    line-height: 1;
    min-width: 28px;
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-remove-item:hover { color: var(--pl-danger); background: #fee2e2; }

/* Associate panel */
.assoc-panel {
    background: var(--pl-surface);
    border-radius: 8px;
    padding: 12px;
    margin-top: 12px;
}
.assoc-type-badge {
    display: inline-block;
    font-size: .65rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    letter-spacing: .4px;
    text-transform: uppercase;
}
.assoc-type-badge.customer { background: #dcfce7; color: #166534; }
.assoc-type-badge.supplier { background: #dbeafe; color: #1e40af; }
.assoc-detail-row {
    display: flex;
    gap: 6px;
    font-size: .75rem;
    color: var(--pl-muted);
    margin-top: 4px;
    align-items: flex-start;
}
.assoc-detail-row strong { color: var(--pl-text); }

/* Cart summary */
.cart-summary { background: #fff; border-top: 2px solid var(--pl-border); padding: 16px 20px; }
.summary-row { display: flex; justify-content: space-between; font-size: .83rem; margin-bottom: 5px; color: var(--pl-text); }
.summary-row.grand { font-size: 1rem; font-weight: 700; color: var(--pl-primary); border-top: 1px solid var(--pl-border); padding-top: 8px; margin-top: 4px; }

/* Create Invoice btn */
.btn-create-invoice {
    width: 100%;
    background: linear-gradient(135deg, var(--pl-accent), #059669);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-size: .9rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 12px;
    transition: all .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.btn-create-invoice:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,.4); }
.btn-create-invoice:disabled { opacity: .5; cursor: not-allowed; }

/* ── Invoice Modal ─────────────────────────────────────── */
.inv-modal-header {
    background: linear-gradient(135deg, #0c763d, #0a783e);
    color: #fff;
    border-radius: 8px 8px 0 0;
    padding: 16px 20px;
}
.inv-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.inv-status-badge.draft     { background: #fef3c7; color: #92400e; }
.inv-status-badge.confirmed { background: #d1fae5; color: #065f46; }

.inv-items-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.inv-items-table th { background: var(--pl-surface); padding: 8px; text-align: left; font-weight: 600; color: var(--pl-muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; }
.inv-items-table td { padding: 8px; border-bottom: 1px solid var(--pl-border); color: var(--pl-text); }
.inv-items-table td.r { text-align: right; font-weight: 600; }
.inv-total-row  { display: flex; justify-content: space-between; font-size: .83rem; padding: 3px 0; color: var(--pl-text); }
.inv-grand-row  { display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 700; color: var(--pl-primary); padding: 6px 0; border-top: 2px solid var(--pl-primary); margin-top: 4px; }

.print-options {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
}
.btn-print-opt {
    flex: 1;
    min-width: 140px;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all .2s;
    border: 2px solid;
}
.btn-normal-print { background: #1e293b; color: #fff; border-color: #1e293b; }
.btn-normal-print:hover { background: #0f172a; }
.btn-thermal-print { background: #065f46; color: #fff; border-color: #065f46; }
.btn-thermal-print:hover { background: #064e3b; }
.btn-confirm-inv { background: var(--pl-primary); color: #fff; border-color: var(--pl-primary); }
.btn-confirm-inv:hover:not(:disabled) { background: #7aaa30; }
.btn-confirm-inv:disabled { opacity: .5; cursor: not-allowed; }

/* ── Select2 custom ────────────────────────────────────── */
.select2-container .select2-selection--single {
    height: 38px !important;
    border: 1px solid var(--pl-border) !important;
    border-radius: 8px !important;
    display: flex;
    align-items: center;
}
.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    padding-left: 12px !important;
    color: var(--pl-text);
    font-size: .84rem;
}
.select2-container .select2-selection__arrow { top: 6px !important; }
.select2-container--open .select2-selection--single { border-color: var(--pl-primary) !important; }

/* ── Pagination ────────────────────────────────────────── */
.pagination { justify-content: center; margin-top: 20px; }
.page-link { color: var(--pl-primary); border-radius: 6px !important; margin: 0 2px; }
.page-item.active .page-link { background: var(--pl-primary); border-color: var(--pl-primary); }

/* ── Mobile ────────────────────────────────────────────── */
@media (max-width: 768px) {
    /* Layout */
    .pl-wrapper       { flex-direction: column; gap: 12px; }
    .pl-sidebar       { width: 100%; position: static; }

    /* Header */
    .pl-header        { padding: 12px 14px; gap: 8px; }
    .pl-header-title  { font-size: 1rem; }
    .pl-header-sub    { font-size: .75rem; }

    /* Toolbar: stack vertically so nothing overflows */
    .pl-toolbar       { flex-direction: column; align-items: flex-start; padding: 8px 12px; gap: 8px; }
    .pl-toolbar-right { width: 100%; justify-content: space-between; }
    .pl-result-count  { font-size: .78rem; }

    /* Product grid */
    #product-grid           { gap: 10px; }
    #product-grid.grid-view { grid-template-columns: repeat(2, 1fr); }

    /* Product card body */
    .product-body     { padding: 8px; gap: 4px; }
    .product-name     { font-size: .76rem; }
    .product-cat      { font-size: .66rem; }

    /* No-image icon smaller in compact cards */
    .no-img-placeholder { font-size: 1.6rem; }

    /* Price chips */
    .price-row        { gap: 4px; }
    .price-chip       { padding: 3px 5px; min-width: 0; }
    .price-chip-label { font-size: .55rem; }
    .price-chip-value { font-size: .78rem; }
    .stock-info       { font-size: .63rem; }

    /* Price mode toggle */
    .price-mode-toggle { font-size: .65rem; }
    .pm-btn           { padding: 2px 5px; font-size: .65rem; }

    /* Add-to-cart row */
    .add-to-cart-row  { gap: 4px; }
    .qty-input        { width: 40px; padding: 4px 3px; font-size: .75rem; }
    .btn-add-cart     { padding: 5px 6px; font-size: .7rem; }

    /* Cart FAB – smaller on phone */
    .cart-fab { bottom: 16px; right: 16px; padding: 10px 16px; font-size: .85rem; }
}
@media (max-width: 480px) {
    /* Single column below 480 px so cards have enough room */
    #product-grid.grid-view { grid-template-columns: 1fr; }

    /* Restore normal card sizing in single-column */
    .product-body     { padding: 12px; gap: 6px; }
    .product-name     { font-size: .83rem; }
    .product-cat      { font-size: .7rem; }
    .price-chip       { padding: 4px 8px; }
    .price-chip-label { font-size: .62rem; }
    .price-chip-value { font-size: .88rem; }
    .stock-info       { font-size: .68rem; }
    .pm-btn           { padding: 2px 6px; font-size: .7rem; }
    .qty-input        { width: 54px; padding: 5px 6px; font-size: .8rem; }
    .btn-add-cart     { padding: 6px 10px; font-size: .78rem; }
}
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ═══════════════════════════════════════════════════════════════
     PAGE HEADER
═════════════════════════════════════════════════════════════════ --}}
<div class="pl-header">
    <div>
        <div class="pl-header-title">
            <i data-lucide="shopping-cart" style="width:22px;height:22px;vertical-align:middle;margin-right:8px;"></i>
            Product List
        </div>
        <div class="pl-header-sub">Browse, add to cart &amp; create instant invoices</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        {{-- Mobile filter toggle --}}
        <button class="btn btn-light btn-sm d-md-none fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#filterSidebar">
            <i data-lucide="sliders-horizontal" style="width:14px;height:14px;"></i> Filters
        </button>
        <a href="{{ route('sales-invoices.index') }}" class="btn btn-light btn-sm fw-semibold">
            <i data-lucide="receipt" style="width:14px;height:14px;"></i> All Invoices
        </a>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     MAIN LAYOUT
═════════════════════════════════════════════════════════════════ --}}
<div class="pl-wrapper">

    {{-- ─── FILTER SIDEBAR ─── --}}
    <div class="pl-sidebar collapse d-md-block" id="filterSidebar">
        <div class="filter-card">
            <div class="filter-card-header">
                <i data-lucide="filter" style="width:14px;height:14px;"></i> Filters
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('product-list.index') }}" id="filterForm">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">

                    {{-- Search --}}
                    <div class="filter-label">Search</div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or SKU…" class="filter-search" id="filterSearch">

                    {{-- Category --}}
                    <div class="filter-label">Category</div>
                    <label class="filter-radio">
                        <input type="radio" name="category_id" value="" {{ !request('category_id') ? 'checked' : '' }}>
                        All Categories
                    </label>
                    @foreach($categories as $cat)
                    <label class="filter-radio">
                        <input type="radio" name="category_id" value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'checked' : '' }}>
                        {{ $cat->name }}
                    </label>
                    @endforeach

                    {{-- Brand --}}
                    <div class="filter-label">Brand</div>
                    <label class="filter-radio">
                        <input type="radio" name="brand_id" value="" {{ !request('brand_id') ? 'checked' : '' }}>
                        All Brands
                    </label>
                    @foreach($brands as $brand)
                    <label class="filter-radio">
                        <input type="radio" name="brand_id" value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'checked' : '' }}>
                        {{ $brand->name }}
                    </label>
                    @endforeach

                    {{-- Stock --}}
                    <div class="filter-label">Availability</div>
                    <label class="filter-radio">
                        <input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }} id="inStockCheck">
                        In Stock Only
                    </label>

                    <button type="submit" class="btn btn-primary btn-sm w-100 mt-3 fw-semibold">Apply Filters</button>
                    <a href="{{ route('product-list.index') }}" class="btn-reset-filters">Reset All</a>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── MAIN CONTENT ─── --}}
    <div class="pl-main">

        {{-- Toolbar --}}
        <div class="pl-toolbar">
            <div class="pl-result-count">
                Showing <strong>{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</strong>
                of <strong>{{ $products->total() }}</strong> products
            </div>
            <div class="pl-toolbar-right">
                <label class="d-flex align-items-center gap-2" style="font-size:.8rem;color:var(--pl-muted);">
                    Per page:
                    <select class="per-page-select" onchange="changePerPage(this.value)">
                        @foreach([12,24,48,96] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="view-toggle">
                    <button class="view-btn active" id="btnGrid" onclick="setView('grid')" title="Grid view">
                        <i data-lucide="grid-3x3" style="width:14px;height:14px;"></i>
                    </button>
                    <button class="view-btn" id="btnList" onclick="setView('list')" title="List view">
                        <i data-lucide="list" style="width:14px;height:14px;"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Search Option --}}
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body p-2">
                <form method="GET" action="{{ route('product-list.index') }}">
                    @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    @endif
                    @if(request('brand_id'))
                        <input type="hidden" name="brand_id" value="{{ request('brand_id') }}">
                    @endif
                    @if(request('in_stock'))
                        <input type="hidden" name="in_stock" value="{{ request('in_stock') }}">
                    @endif
                    @if(request('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i data-lucide="search" style="width:16px;height:16px;color:var(--pl-muted);"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search products by SKU or Name..." value="{{ request('search') }}">
                        <button class="btn btn-primary px-4 fw-semibold" type="submit">Search</button>
                        @if(request('search'))
                            <a href="{{ route('product-list.index', request()->except('search')) }}" class="btn btn-outline-secondary d-flex align-items-center">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" role="alert" style="margin-bottom:15px;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert" style="margin-bottom:15px;">
                {{ session('error') }}
            </div>
        @endif
        {{-- Product Grid --}}
        <div id="product-grid" class="grid-view">
            @forelse($products as $product)
            @php
                $boxPrice  = $product->price?->sales_price_box  ?? 0;
                $pieceRate = $product->price?->sales_price_item ?? 0;
                $stock     = $product->stock_qty_pcs ?? 0;
                $stockBox  = $product->stock_qty_box ?? 0;
                $hasStock  = ($stock > 0 || $stockBox > 0);
                $imgUrl    = $product->image
                    ? \Storage::disk('public_uploads')->url($product->image->image_path)
                    : null;
            @endphp
            <div class="product-card" id="pcard-{{ $product->id }}">
                {{-- Image --}}
                <div class="product-img-wrap">
                    @if($imgUrl)
                        <img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                        <div class="no-img-placeholder">
                            <i data-lucide="package-2" style="width:40px;height:40px;"></i>
                        </div>
                    @endif
                    <span class="product-badge-sku">{{ $product->sku }}</span>
                    @if(!$hasStock)
                    <div class="out-of-stock-overlay">OUT OF STOCK</div>
                    @endif
                </div>

                {{-- Body --}}
                <div class="product-body">
                    <div class="product-name">{{ $product->name }}</div>
                    @if($product->category)
                    <div class="product-cat">{{ $product->category->name }}</div>
                    @endif

                    <div class="price-row">
                        <div class="price-chip">
                            <span class="price-chip-label">Box Price</span>
                            <span class="price-chip-value">${{ number_format($boxPrice, 2) }}</span>
                        </div>
                        <div class="price-chip">
                            <span class="price-chip-label">Per Piece</span>
                            <span class="price-chip-value">${{ number_format($pieceRate, 2) }}</span>
                        </div>
                    </div>

                    @php
                        $stockParts = [];
                        if ($stock > 0) $stockParts[] = $stock . ' pcs';
                        if ($stockBox > 0) $stockParts[] = $stockBox . ' box';
                        $stockLabel = $hasStock ? implode(' · ', $stockParts) : 'No stock';
                        $stockClass = !$hasStock ? 'out' : ($stock < 10 && $stockBox == 0 ? 'low' : '');
                    @endphp
                    <div class="stock-info {{ $stockClass }}">{{ $stockLabel }}</div>

                    <div style="margin-top:6px;">
                        <div class="price-mode-toggle mb-1">
                            <button class="pm-btn active" onclick="setPriceMode({{ $product->id }}, 'piece', this)" data-price="{{ $pieceRate }}">Per Pc</button>
                            <button class="pm-btn" onclick="setPriceMode({{ $product->id }}, 'box', this)" data-price="{{ $boxPrice }}">Per Box</button>
                        </div>
                    </div>

                    <div class="add-to-cart-row">
                        <input type="number" class="qty-input" id="qty-{{ $product->id }}" value="1" min="1" step="1">
                        <button class="btn-add-cart" id="addBtn-{{ $product->id }}"
                            onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ $product->sku }}', '{{ $imgUrl ?? '' }}', {{ $stock }}, {{ $stockBox }})"
                            {{ !$hasStock ? 'disabled style=opacity:.45;cursor:not-allowed' : '' }}>
                            <i data-lucide="plus" style="width:12px;height:12px;"></i> Add
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--pl-muted);">
                <i data-lucide="search-x" style="width:48px;height:48px;stroke:#cbd5e1;display:block;margin:0 auto 12px;"></i>
                <div style="font-size:1rem;font-weight:600;">No products found</div>
                <div style="font-size:.83rem;margin-top:4px;">Try adjusting your filters</div>
                <a href="{{ route('product-list.index') }}" class="btn btn-primary btn-sm mt-3">Clear Filters</a>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="mt-3">
            {{ $products->links() }}
        </div>
        @endif
    </div>{{-- /.pl-main --}}
</div>{{-- /.pl-wrapper --}}

{{-- ═══════════════════════════════════════════════════════════════
     FLOATING CART BUTTON
═════════════════════════════════════════════════════════════════ --}}
<button class="cart-fab" onclick="openCart()" id="cartFab" style="display:none;">
    <i data-lucide="shopping-cart" style="width:18px;height:18px;"></i>
    Cart
    <span class="badge" id="cartFabBadge">0</span>
</button>

{{-- ═══════════════════════════════════════════════════════════════
     CART OFFCANVAS
═════════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end offcanvas-cart" tabindex="-1" id="cartOffcanvas">
    <div class="cart-header d-flex align-items-center justify-content-between">
        <h5>
            <i data-lucide="shopping-cart" style="width:16px;height:16px;vertical-align:middle;margin-right:6px;"></i>
            Invoice Cart
            <span class="badge ms-1" id="cartHeaderBadge">0</span>
        </h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:none;" onclick="clearCart()">
                <i data-lucide="trash-2" style="width:13px;height:13px;"></i> Clear
            </button>
            <button type="button" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;" data-bs-dismiss="offcanvas">✕</button>
        </div>
    </div>

    <div class="offcanvas-body d-flex flex-column" style="padding:16px 20px;overflow-y:auto;">

        {{-- Cart Items List (rendered entirely by JS) --}}
        <div id="cartItemsList"></div>

        {{-- Associate Selection --}}
        <div style="margin-top:16px;">
            <label style="font-size:.75rem;font-weight:700;color:var(--pl-muted);text-transform:uppercase;letter-spacing:.6px;display:block;margin-bottom:6px;">
                <i data-lucide="user" style="width:12px;height:12px;"></i> Select Associate (Customer / Supplier)
            </label>
            <select id="associateSelect" style="width:100%;">
                <option value="">— Select Associate —</option>
                @foreach($associates->groupBy('type') as $type => $group)
                    <optgroup label="{{ $type }}s">
                        @foreach($group as $assoc)
                        <option value="{{ $assoc->id }}" data-type="{{ $assoc->type }}">
                            [{{ $assoc->code ?? '--' }}] {{ $assoc->name }}
                        </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            {{-- Associate Details Panel --}}
            <div class="assoc-panel" id="assocPanel" style="display:none;">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <strong style="font-size:.85rem;" id="assocName">—</strong>
                    <span class="assoc-type-badge" id="assocTypeBadge">—</span>
                </div>
                <div class="assoc-detail-row" id="assocAddress" style="display:none;">
                    <i data-lucide="map-pin" style="width:11px;height:11px;flex-shrink:0;margin-top:2px;"></i>
                    <span id="assocAddressText"></span>
                </div>
                <div class="assoc-detail-row" id="assocPhone" style="display:none;">
                    <i data-lucide="phone" style="width:11px;height:11px;flex-shrink:0;"></i>
                    <span id="assocPhoneText"></span>
                </div>
                <div class="assoc-detail-row" id="assocEmail" style="display:none;">
                    <i data-lucide="mail" style="width:11px;height:11px;flex-shrink:0;"></i>
                    <span id="assocEmailText"></span>
                </div>
                <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                    <div style="background:#fff;border-radius:6px;padding:5px 10px;border:1px solid var(--pl-border);font-size:.75rem;">
                        <div style="color:var(--pl-muted);">Credit Limit</div>
                        <div style="font-weight:700;color:var(--pl-text);" id="assocCredit">—</div>
                    </div>
                    <div style="background:#fff;border-radius:6px;padding:5px 10px;border:1px solid var(--pl-border);font-size:.75rem;">
                        <div style="color:var(--pl-muted);">Outstanding</div>
                        <div style="font-weight:700;color:var(--pl-danger);" id="assocOutstanding">—</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Remarks --}}
        <div style="margin-top:12px;">
            <label style="font-size:.75rem;font-weight:700;color:var(--pl-muted);text-transform:uppercase;letter-spacing:.6px;display:block;margin-bottom:6px;">
                Remarks (optional)
            </label>
            <textarea id="invoiceRemarks" rows="2" style="width:100%;border:1px solid var(--pl-border);border-radius:6px;padding:8px;font-size:.82rem;resize:none;color:var(--pl-text);outline:none;"
                placeholder="e.g. Deliver by Monday…"></textarea>
        </div>

        <div style="flex:1;"></div>
    </div>

    {{-- Cart Summary + Action --}}
    <div class="cart-summary">
        <div class="summary-row">
            <span>Subtotal (<span id="summaryItems">0</span> items)</span>
            <span id="summarySubtotal">$0.00</span>
        </div>
        <div class="summary-row grand">
            <span>Total</span>
            <span id="summaryTotal">$0.00</span>
        </div>
        <button class="btn-create-invoice" id="btnCreateInvoice" onclick="createInvoice()" disabled>
            <i data-lucide="file-plus" style="width:16px;height:16px;"></i>
            Create Invoice
        </button>
        <p id="invoiceHint" style="font-size:.7rem;text-align:center;color:var(--pl-muted);margin-top:6px;">
            Select an associate to enable invoice creation
        </p>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     INVOICE PREVIEW MODAL
═════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="invoiceModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:10px;overflow:hidden;">

            <div class="inv-modal-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="mb-1" style="font-weight:700;">
                            <i data-lucide="receipt" style="width:16px;height:16px;vertical-align:middle;margin-right:6px;"></i>
                            Invoice Preview
                        </h5>
                        <div style="font-size:.82rem;opacity:.85;" id="modalInvNumber">—</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="inv-status-badge draft" id="invStatusBadge">Draft</span>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
            </div>

            <div class="modal-body" style="padding:20px;">
                {{-- Associate & Date --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <div style="background:var(--pl-surface);border-radius:8px;padding:12px;">
                            <div style="font-size:.7rem;font-weight:700;color:var(--pl-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Bill To</div>
                            <div style="font-weight:700;font-size:.92rem;" id="modalCustomerName">—</div>
                            <div style="font-size:.78rem;color:var(--pl-muted);margin-top:3px;" id="modalCustomerAddr">—</div>
                            <span class="assoc-type-badge mt-2" id="modalCustomerType">—</span>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div style="background:var(--pl-surface);border-radius:8px;padding:12px;">
                            <div style="font-size:.7rem;font-weight:700;color:var(--pl-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Invoice Details</div>
                            <div style="font-size:.8rem;display:flex;justify-content:space-between;margin-bottom:3px;">
                                <span style="color:var(--pl-muted);">Invoice No</span>
                                <strong id="modalInvNo">—</strong>
                            </div>
                            <div style="font-size:.8rem;display:flex;justify-content:space-between;margin-bottom:3px;">
                                <span style="color:var(--pl-muted);">Date</span>
                                <strong id="modalInvDate">—</strong>
                            </div>
                            <div style="font-size:.8rem;display:flex;justify-content:space-between;">
                                <span style="color:var(--pl-muted);">Payment</span>
                                <strong id="modalPayStatus">—</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <div style="overflow-x:auto;">
                    <table class="inv-items-table">
                        <thead>
                            <tr>
                                <th style="width:35%;">Description</th>
                                <th>SKU</th>
                                <th style="text-align:right;">Qty</th>
                                <th style="text-align:right;">Unit Price</th>
                                <th style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsBody">
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div style="display:flex;justify-content:flex-end;margin-top:14px;">
                    <div style="width:240px;">
                        <div class="inv-total-row">
                            <span style="color:var(--pl-muted);">Subtotal</span>
                            <span id="modalSubtotal">$0.00</span>
                        </div>
                        <div class="inv-total-row" id="modalDiscountRow" style="display:none;">
                            <span style="color:var(--pl-muted);">Discount</span>
                            <span id="modalDiscount"></span>
                        </div>
                        <div class="inv-total-row" id="modalTaxRow" style="display:none;">
                            <span style="color:var(--pl-muted);">Tax</span>
                            <span id="modalTax"></span>
                        </div>
                        <div class="inv-grand-row">
                            <span>Grand Total</span>
                            <span id="modalTotal">$0.00</span>
                        </div>
                    </div>
                </div>

                <hr style="border-color:var(--pl-border);margin:16px 0;">

                {{-- Action Buttons --}}
                <div>
                    <div style="font-size:.75rem;font-weight:700;color:var(--pl-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
                        Actions
                    </div>
                    <button class="btn-print-opt btn-confirm-inv" id="btnConfirmInv" onclick="confirmInvoice()">
                        <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                        Confirm Invoice
                    </button>

                    <div class="print-options">
                        <button class="btn-print-opt btn-normal-print" onclick="printInvoice('normal')">
                            <i data-lucide="printer" style="width:14px;height:14px;"></i>
                            Normal Print (A4)
                        </button>
                        <button class="btn-print-opt btn-thermal-print" onclick="printInvoice('thermal')">
                            <i data-lucide="receipt" style="width:14px;height:14px;"></i>
                            Thermal Print (80mm)
                        </button>
                    </div>

                    <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="#" id="btnPayLink" class="btn btn-success btn-sm fw-semibold" style="display:none;">
                            <i data-lucide="credit-card" style="width:13px;height:13px;"></i> Record Payment
                        </a>
                        <button class="btn btn-outline-secondary btn-sm fw-semibold" onclick="startNewCart()">
                            <i data-lucide="plus-circle" style="width:13px;height:13px;"></i> New Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
/* ══════════════════════════════════════════════════════════
   STATE
══════════════════════════════════════════════════════════ */
var cart    = JSON.parse(localStorage.getItem('pl_cart') || '[]');
var priceMode = {};   // productId → 'piece' | 'box'
var selectedAssociate = null;
var currentInvoiceId  = null;

/* ══════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();

    // ── Cart item actions via event delegation (robust against innerHTML re-renders)
    document.getElementById('cartItemsList').addEventListener('click', function(e) {
        var removeBtn = e.target.closest('[data-action="remove"]');
        if (removeBtn) { removeFromCart(parseInt(removeBtn.dataset.idx, 10)); return; }

        var decBtn = e.target.closest('[data-action="qty-dec"]');
        if (decBtn) { updateQty(parseInt(decBtn.dataset.idx, 10), -1); return; }

        var incBtn = e.target.closest('[data-action="qty-inc"]');
        if (incBtn) { updateQty(parseInt(incBtn.dataset.idx, 10), 1); return; }
    });

    document.getElementById('cartItemsList').addEventListener('change', function(e) {
        if (e.target.classList.contains('cart-qty-input')) {
            setQty(parseInt(e.target.dataset.idx, 10), e.target.value);
        }
    });

    // ── Associate select: native listener (works reliably inside offcanvas)
    var assocNative = document.getElementById('associateSelect');
    assocNative.addEventListener('change', function () {
        var id = this.value;
        if (id) loadAssociateDetails(id);
        else    clearAssociatePanel();
    });

    // ── Select2 enhancement (visual only; logic handled by native above)
    if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
        $('#associateSelect').select2({
            dropdownParent: $('#cartOffcanvas'),
            placeholder: '— Select Associate —',
            allowClear: true,
        });
    }

    renderCart();
    updateFab();
});

/* ══════════════════════════════════════════════════════════
   VIEW TOGGLE & PER PAGE
══════════════════════════════════════════════════════════ */
function setView(mode) {
    var grid = document.getElementById('product-grid');
    grid.className = mode + '-view';
    document.querySelectorAll('.product-card').forEach(function(c) {
        c.classList.toggle('list-view', mode === 'list');
    });
    document.getElementById('btnGrid').classList.toggle('active', mode === 'grid');
    document.getElementById('btnList').classList.toggle('active', mode === 'list');
    localStorage.setItem('pl_view', mode);
}

// restore saved view
(function() {
    var v = localStorage.getItem('pl_view') || 'grid';
    if (v === 'list') setView('list');
}());

function changePerPage(val) {
    var url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    window.location = url.toString();
}

/* ══════════════════════════════════════════════════════════
   PRICE MODE (per piece / per box)
══════════════════════════════════════════════════════════ */
function setPriceMode(productId, mode, btn) {
    priceMode[productId] = mode;
    var wrap = btn.closest('.price-mode-toggle');
    wrap.querySelectorAll('.pm-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
}

function getPrice(productId, btn) {
    var mode = priceMode[productId] || 'piece';
    var wrap = btn.closest('.product-card').querySelector('.price-mode-toggle');
    var activeBtn = wrap.querySelector('.pm-btn.active');
    return parseFloat(activeBtn ? activeBtn.dataset.price : 0);
}

/* ══════════════════════════════════════════════════════════
   CART LOGIC
══════════════════════════════════════════════════════════ */
function addToCart(productId, name, sku, imgUrl, stockPcs, stockBox) {
    var btn   = document.getElementById('addBtn-' + productId);
    var qtyEl = document.getElementById('qty-' + productId);
    var qty   = parseFloat(qtyEl.value) || 1;
    var price = getPrice(productId, btn);
    var mode  = priceMode[productId] || 'piece';

    // Ensure quantity is not less than 1
    if (qty < 1 || price < 0) {
        alert('Quantity must be at least 1');
        return;
    }

    var maxStock = (mode === 'box') 
        ? (stockBox !== undefined ? parseFloat(stockBox) : 999999) 
        : (stockPcs !== undefined ? parseFloat(stockPcs) : 999999);

    var existing = cart.find(function(i) { return i.id === productId && i.mode === mode; });
    if (existing) {
        if (existing.qty + qty > maxStock) {
            alert('Cannot exceed available stock (' + maxStock + ')');
            return;
        }
        existing.qty += qty;
    } else {
        if (qty > maxStock) {
            alert('Cannot exceed available stock (' + maxStock + ')');
            return;
        }
        cart.push({ 
            id: productId, 
            name: name, 
            sku: sku, 
            img: imgUrl, 
            qty: qty, 
            price: price, 
            mode: mode,
            stockPcs: stockPcs,
            stockBox: stockBox
        });
    }

    saveCart();
    renderCart();
    updateFab();
    flashBtn(btn);

    // Auto-open cart on first add
    if (cart.length === 1) openCart();
}

function flashBtn(btn) {
    btn.classList.add('success');
    setTimeout(function() { btn.classList.remove('success'); }, 1200);
}

function removeFromCart(idx) {
    cart.splice(idx, 1);
    saveCart();
    renderCart();
    updateFab();
}

function updateQty(idx, delta) {
    var item = cart[idx];
    var maxStock = (item.mode === 'box') 
        ? (item.stockBox !== undefined ? parseFloat(item.stockBox) : 999999) 
        : (item.stockPcs !== undefined ? parseFloat(item.stockPcs) : 999999);
    var newQty = parseFloat(item.qty) + delta;

    if (newQty < 1) {
        return; // Don't allow less than 1
    }
    if (newQty > maxStock) {
        alert('Cannot exceed available stock (' + maxStock + ')');
        return;
    }

    item.qty = newQty;
    saveCart();
    renderCart();
    updateFab();
}

function setQty(idx, val) {
    var item = cart[idx];
    var maxStock = (item.mode === 'box') 
        ? (item.stockBox !== undefined ? parseFloat(item.stockBox) : 999999) 
        : (item.stockPcs !== undefined ? parseFloat(item.stockPcs) : 999999);
    var n = parseFloat(val);

    if (isNaN(n) || n < 1) {
        n = 1;
    } else if (n > maxStock) {
        alert('Cannot exceed available stock (' + maxStock + ')');
        n = maxStock;
    }

    item.qty = n;
    saveCart();
    renderCart();
    updateFab();
    updateSummary();
}

function clearCart() {
    if (cart.length && !confirm('Clear all items?')) return;
    cart = [];
    saveCart();
    renderCart();
    updateFab();
}

function saveCart() { localStorage.setItem('pl_cart', JSON.stringify(cart)); }

function getCartTotal() {
    return cart.reduce(function(s, i) { return s + (i.qty * i.price); }, 0);
}

/* ── Render ── */
var CART_EMPTY_HTML =
    '<div class="cart-empty">' +
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:56px;height:56px;stroke:#cbd5e1;display:block;margin:0 auto 12px;">' +
    '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>' +
    '<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>' +
    '</svg>Your cart is empty.<br><small>Add products to get started.</small></div>';

function renderCart() {
    var list = document.getElementById('cartItemsList');

    if (cart.length === 0) {
        list.innerHTML = CART_EMPTY_HTML;
        updateSummary();
        return;
    }

    var html = '';
    cart.forEach(function(item, idx) {
        var total = (item.qty * item.price).toFixed(2);
        var imgHtml = item.img
            ? '<img src="' + item.img + '" class="cart-item-img" alt="">'
            : '<div class="cart-item-img" style="background:var(--pl-surface);display:flex;align-items:center;justify-content:center;color:#cbd5e1;border-radius:6px;font-size:1.2rem;">&#128246;</div>';

        html += '<div class="cart-item">';
        html += imgHtml;
        html += '<div style="flex:1;min-width:0;">';
        html += '<div class="cart-item-name">' + escHtml(item.name) + '</div>';
        html += '<div class="cart-item-sku">' + escHtml(item.sku) + ' &middot; <span style="color:var(--pl-primary);">' + (item.mode === 'box' ? 'Per Box' : 'Per Pc') + '</span></div>';
        html += '<div class="cart-qty-control">';
        html += '<button type="button" class="cart-qty-btn" data-action="qty-dec" data-idx="' + idx + '">&#8722;</button>';
        html += '<input class="cart-qty-input" type="number" value="' + item.qty + '" min="1" step="1" data-idx="' + idx + '">';
        html += '<button type="button" class="cart-qty-btn" data-action="qty-inc" data-idx="' + idx + '">+</button>';
        html += '<span class="cart-item-price ms-2">@ $' + parseFloat(item.price).toFixed(2) + '</span>';
        html += '</div></div>';
        html += '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">';
        html += '<div class="cart-item-total">$' + total + '</div>';
        html += '<button type="button" class="btn-remove-item" data-action="remove" data-idx="' + idx + '" title="Remove item">&times;</button>';
        html += '</div>';
        html += '</div>';
    });

    list.innerHTML = html;
    updateSummary();
}

function updateSummary() {
    var total = getCartTotal();
    var count = cart.reduce(function(s, i) { return s + i.qty; }, 0);
    document.getElementById('summaryItems').textContent   = cart.length;
    document.getElementById('summarySubtotal').textContent = '$' + total.toFixed(2);
    document.getElementById('summaryTotal').textContent    = '$' + total.toFixed(2);
    updateCreateBtn();
}

function updateFab() {
    var fab = document.getElementById('cartFab');
    var badge = document.getElementById('cartFabBadge');
    var headerBadge = document.getElementById('cartHeaderBadge');
    var count = cart.length;
    fab.style.display = count > 0 ? '' : 'none';
    badge.textContent = count;
    headerBadge.textContent = count;
}

function updateCreateBtn() {
    var btn  = document.getElementById('btnCreateInvoice');
    var hint = document.getElementById('invoiceHint');
    var canCreate = cart.length > 0 && selectedAssociate !== null;
    btn.disabled = !canCreate;
    hint.textContent = cart.length === 0
        ? 'Add products to your cart first'
        : !selectedAssociate
            ? 'Select an associate to enable invoice creation'
            : '';
}

/* ══════════════════════════════════════════════════════════
   CART OFFCANVAS
══════════════════════════════════════════════════════════ */
function openCart() {
    var oc = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cartOffcanvas'));
    oc.show();
    lucide.createIcons();
}

/* ══════════════════════════════════════════════════════════
   ASSOCIATE DETAILS
══════════════════════════════════════════════════════════ */
function loadAssociateDetails(id) {
    fetch('/api/product-list/associate/' + id)
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(d) {
            selectedAssociate = d;
            var panel = document.getElementById('assocPanel');
            panel.style.display = '';
            document.getElementById('assocName').textContent = d.name;
            var typeBadge = document.getElementById('assocTypeBadge');
            typeBadge.textContent = d.type;
            typeBadge.className = 'assoc-type-badge ' + (d.type === 'Customer' ? 'customer' : 'supplier');

            setField('assocAddress', 'assocAddressText', d.address);
            setField('assocPhone',   'assocPhoneText',   d.phone);
            setField('assocEmail',   'assocEmailText',   d.email);

            document.getElementById('assocCredit').textContent      = d.currency_symbol + parseFloat(d.credit_limit || 0).toFixed(2);
            document.getElementById('assocOutstanding').textContent  = d.currency_symbol + parseFloat(d.outstanding || 0).toFixed(2);

            updateCreateBtn();
            lucide.createIcons();
        })
        .catch(function() { clearAssociatePanel(); });
}

function setField(rowId, textId, val) {
    var row = document.getElementById(rowId);
    if (val) {
        document.getElementById(textId).textContent = val;
        row.style.display = 'flex';
    } else {
        row.style.display = 'none';
    }
}

function clearAssociatePanel() {
    selectedAssociate = null;
    document.getElementById('assocPanel').style.display = 'none';
    updateCreateBtn();
}

/* ══════════════════════════════════════════════════════════
   CREATE INVOICE
══════════════════════════════════════════════════════════ */
function createInvoice() {
    if (!selectedAssociate || cart.length === 0) return;

    var btn = document.getElementById('btnCreateInvoice');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating…';

    var items = cart.map(function(i) {
        return {
            product_id:  i.id,
            qty:         i.qty,
            unit_price:  i.price,
            description: i.name,
            mode:        i.mode || 'piece',
        };
    });

    fetch('/api/product-list/invoice', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            associate_id: selectedAssociate.id,
            items:        items,
            remarks:      document.getElementById('invoiceRemarks').value,
        }),
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="file-plus" style="width:16px;height:16px;"></i> Create Invoice';
        lucide.createIcons();

        if (d.success) {
            currentInvoiceId = d.invoice_id;
            cart = [];
            saveCart();
            renderCart();
            updateFab();
            // Close cart, open invoice modal
            bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas'))?.hide();
            loadAndShowInvoice(d.invoice_id);
        } else {
            alert('Error: ' + (d.message || 'Failed to create invoice.'));
        }
    })
    .catch(function(e) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="file-plus" style="width:16px;height:16px;"></i> Create Invoice';
        lucide.createIcons();
        alert('Network error. Please try again.');
    });
}

/* ══════════════════════════════════════════════════════════
   INVOICE MODAL
══════════════════════════════════════════════════════════ */
function loadAndShowInvoice(id) {
    fetch('/api/product-list/invoice/' + id)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            // Header
            document.getElementById('modalInvNumber').textContent = d.invoice_number;
            document.getElementById('modalInvNo').textContent     = d.invoice_number;
            document.getElementById('modalInvDate').textContent   = d.invoice_date;
            document.getElementById('modalPayStatus').textContent = capitalize(d.payment_status);

            // Associate
            document.getElementById('modalCustomerName').textContent = d.customer_name;
            document.getElementById('modalCustomerAddr').textContent = d.billing_address || '—';
            var typeBadge = document.getElementById('modalCustomerType');
            typeBadge.textContent  = d.customer_type;
            typeBadge.className    = 'assoc-type-badge ' + (d.customer_type === 'Customer' ? 'customer' : 'supplier');

            // Status
            var statusBadge = document.getElementById('invStatusBadge');
            statusBadge.textContent = capitalize(d.status);
            statusBadge.className   = 'inv-status-badge ' + d.status;

            // Items
            var tbody = document.getElementById('modalItemsBody');
            var sym   = d.currency_symbol;
            tbody.innerHTML = '';
            d.items.forEach(function(item) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + escHtml(item.description) + (item.uom ? ' <small style="color:var(--pl-muted);">(' + escHtml(item.uom) + ')</small>' : '') + '</td>' +
                    '<td style="color:var(--pl-muted);font-size:.75rem;">' + escHtml(item.sku) + '</td>' +
                    '<td class="r">' + parseFloat(item.qty).toFixed(2) + '</td>' +
                    '<td class="r">' + sym + parseFloat(item.unit_price).toFixed(2) + '</td>' +
                    '<td class="r">' + sym + parseFloat(item.total_price).toFixed(2) + '</td>';
                tbody.appendChild(tr);
            });

            // Totals
            document.getElementById('modalSubtotal').textContent = sym + parseFloat(d.subtotal).toFixed(2);
            document.getElementById('modalTotal').textContent    = sym + parseFloat(d.net_total).toFixed(2);

            if (d.discount_amount > 0) {
                document.getElementById('modalDiscountRow').style.display = 'flex';
                document.getElementById('modalDiscount').textContent = '−' + sym + parseFloat(d.discount_amount).toFixed(2);
            }
            if (d.tax_amount > 0) {
                document.getElementById('modalTaxRow').style.display = 'flex';
                document.getElementById('modalTax').textContent = sym + parseFloat(d.tax_amount).toFixed(2);
            }

            // Confirm button
            var confirmBtn = document.getElementById('btnConfirmInv');
            confirmBtn.disabled = !d.can_confirm;
            confirmBtn.textContent = d.can_confirm ? '✓  Confirm Invoice' : '✓  Confirmed';

            // Pay link
            var payLink = document.getElementById('btnPayLink');
            payLink.href = '/sales-invoices/' + id + '/pay';
            if (!d.can_confirm) {
                payLink.style.display = '';
            } else {
                payLink.style.display = 'none';
            }

            var modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
            modal.show();
            lucide.createIcons();
        })
        .catch(function() { alert('Could not load invoice details.'); });
}

function confirmInvoice() {
    if (!currentInvoiceId) return;
    var btn = document.getElementById('btnConfirmInv');
    btn.disabled = true;
    btn.textContent = 'Confirming…';

    fetch('/api/product-list/invoice/' + currentInvoiceId + '/confirm', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            btn.textContent = '✓  Confirmed';

            var statusBadge = document.getElementById('invStatusBadge');
            statusBadge.textContent = 'Confirmed';
            statusBadge.className   = 'inv-status-badge confirmed';

            var payLink = document.getElementById('btnPayLink');
            payLink.href = '/sales-invoices/' + currentInvoiceId + '/pay';
            payLink.style.display = '';
        } else {
            btn.disabled = false;
            btn.textContent = '✓  Confirm Invoice';
            alert(d.message || 'Could not confirm invoice.');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = '✓  Confirm Invoice';
        alert('Network error. Please try again.');
    });
}

function printInvoice(type) {
    if (!currentInvoiceId) return;
    if (type === 'thermal') {
        var savedMm = 80;
        try { savedMm = parseInt(localStorage.getItem('tp_paper_mm')) || 80; } catch (e) {}
        window.printViaBle(currentInvoiceId, savedMm);
    } else {
        var url = '/product-list/invoice/' + currentInvoiceId + '/print';
        window.open(url, '_blank');
    }
}

function startNewCart() {
    var modal = bootstrap.Modal.getInstance(document.getElementById('invoiceModal'));
    if (modal) modal.hide();
    cart = [];
    saveCart();
    renderCart();
    updateFab();
    selectedAssociate = null;
    $('#associateSelect').val(null).trigger('change');
    clearAssociatePanel();
    document.getElementById('invoiceRemarks').value = '';
    currentInvoiceId = null;
}

/* ══════════════════════════════════════════════════════════
   UTILITIES
══════════════════════════════════════════════════════════ */
function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

// Auto-submit filter form on radio change
document.querySelectorAll('#filterForm input[type="radio"]').forEach(function(r) {
    r.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});
document.getElementById('inStockCheck').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});
</script>
@endpush
