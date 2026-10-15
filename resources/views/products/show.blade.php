@extends('layouts.app')

@section('content')
@php
    $returnUrl = request('return_url');
    if (! $returnUrl || ! str_starts_with($returnUrl, url('/'))) {
        $returnUrl = route('products.index');
    }
@endphp
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Product Details</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ $returnUrl }}">Products</a></li>
                    <li class="breadcrumb-item active">{{ $product->sku }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @if($product->image)
    <div class="col-12 mb-3">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Product Image</h5></div>
            <div class="card-body">
                <img src="{{ \Storage::disk('public_uploads')->url($product->image->image_path) }}"
                     style="width:130px;height:130px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;"
                     alt="{{ $product->name }}">
            </div>
        </div>
    </div>
    @endif

    <div class="col-lg-8">
        <!-- Basic Information -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Basic Information</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted mb-1">Product Name</label>
                        <h5 class="fs-15">{{ $product->name }}</h5>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted mb-1">SKU</label>
                        <h5 class="fs-15">{{ $product->sku }}</h5>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted mb-1">Type</label>
                        <div><span class="badge bg-info">{{ $product->product_type }}</span></div>
                    </div>
                    <div class="col-md-6">
                         <label class="text-muted mb-1">Status</label>
                        <div>
                            @if($product->status)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
                @if($product->expiry_date)
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted mb-1">Expiry Date</label>
                        <h5 class="fs-15">{{ $product->expiry_date->format('d M Y') }}</h5>
                    </div>
                </div>
                @endif
                <div class="row">
                    <div class="col-12">
                        <label class="text-muted mb-1">Description</label>
                        <p class="text-muted mb-0">{{ $product->description ?? 'No description available.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Inventory & Stock</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="p-3 border border-dashed rounded text-center">
                            <p class="text-muted mb-1">Stock (PCS)</p>
                            <h5 class="mb-0 text-primary">{{ number_format($product->stock_qty_pcs, 2) }}</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border border-dashed rounded text-center">
                            <p class="text-muted mb-1">Stock (Box)</p>
                            <h5 class="mb-0 text-primary">{{ number_format($product->stock_qty_box, 2) }}</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border border-dashed rounded text-center">
                            <p class="text-muted mb-1">Min Stock</p>
                            <h5 class="mb-0">{{ $product->min_stock_qty ?? 0 }}</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border border-dashed rounded text-center">
                            <p class="text-muted mb-1">Reorder Qty</p>
                            <h5 class="mb-0">{{ $product->reorder_qty ?? 0 }}</h5>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" disabled {{ $product->inventory_control ? 'checked' : '' }}>
                            <label class="form-check-label">Inventory Control</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" disabled {{ $product->inventory?->have_batch ? 'checked' : '' }}>
                            <label class="form-check-label">Batch Tracking</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" disabled {{ $product->inventory?->have_serial ? 'checked' : '' }}>
                            <label class="form-check-label">Serial Tracking</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Pricing -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Pricing</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="ps-0" scope="row">Cost Price (Item):</th>
                                <td class="text-end">{{ $product->price?->cost_price_item ? number_format($product->price->cost_price_item, 2) : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0" scope="row">Cost Price (Box):</th>
                                <td class="text-end">{{ $product->price?->cost_price_box ? number_format($product->price->cost_price_box, 2) : '-' }}</td>
                            </tr>
                            <tr class="border-top">
                                <th class="ps-0 py-2" scope="row">Sales Price (Item):</th>
                                <td class="text-end py-2 fw-bold text-success">{{ $product->price?->sales_price_item ? number_format($product->price->sales_price_item, 2) : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0" scope="row">Sales Price (Box):</th>
                                <td class="text-end fw-bold text-success">{{ $product->price?->sales_price_box ? number_format($product->price->sales_price_box, 2) : '-' }}</td>
                            </tr>
                            <tr class="border-top">
                                <th class="ps-0 py-2" scope="row">Retail Price (Item):</th>
                                <td class="text-end py-2">{{ $product->price?->retail_price_item ? number_format($product->price->retail_price_item, 2) : '-' }}</td>
                            </tr>
                             <tr>
                                <th class="ps-0" scope="row">Retail Price (Box):</th>
                                <td class="text-end">{{ $product->price?->retail_price_box ? number_format($product->price->retail_price_box, 2) : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Classification -->
        <div class="card">
             <div class="card-header">
                <h5 class="card-title mb-0">Classification</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Category</p>
                                <h6 class="mb-0">{{ $product->category->name ?? '-' }}</h6>
                            </div>
                        </div>
                    </li>
                    <li class="mb-3">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Brand</p>
                                <h6 class="mb-0">{{ $product->brand->name ?? '-' }}</h6>
                            </div>
                        </div>
                    </li>
                    <li class="mb-3">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Unit of Measure</p>
                                <h6 class="mb-0">{{ $product->uom->name ?? '-' }}</h6>
                            </div>
                        </div>
                    </li>
                     <li>
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Barcode</p>
                                @if($product->barcode)
                                    <div id="barcodeCard" class="text-center p-2 rounded" style="background:#fff;border:1px solid #dee2e6;">
                                        <svg id="productBarcode"></svg>
                                        <div style="font-size:.7rem;font-family:monospace;letter-spacing:.05em;margin-top:2px;">{{ $product->barcode }}</div>
                                    </div>
                                    <button type="button" onclick="printBarcode()" class="btn btn-sm btn-outline-secondary mt-2 w-100">
                                        <i class="ti ti-printer me-1"></i> Print Barcode
                                    </button>
                                @else
                                    <h6 class="mb-0">-</h6>
                                @endif
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Product Aliases --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Product Aliases <span class="badge bg-secondary ms-1">{{ $product->aliases->count() }}</span></h5>
                @if($canEdit)
                <a href="{{ route('products.edit', ['product' => $product->id, 'return_url' => $returnUrl, 'tab' => 'aliases']) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-plus me-1"></i> Manage Aliases
                </a>
                @endif
            </div>
            <div class="card-body">
                @if($product->aliases->isEmpty())
                    <p class="text-muted mb-0">No aliases configured. Aliases let you sell this product under different names to different customers while tracking stock from this product.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Alias Name</th>
                                    <th>Alias SKU</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->aliases as $alias)
                                <tr>
                                    <td><strong>{{ $alias->alias_name }}</strong></td>
                                    <td>{{ $alias->alias_sku ?? '-' }}</td>
                                    <td>{{ $alias->description ?? '-' }}</td>
                                    <td>
                                        @if($alias->status)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 text-end">
        @if($canEdit)
        <a href="{{ route('products.edit', ['product' => $product->id, 'return_url' => $returnUrl]) }}" class="btn btn-primary"><i class="ti ti-pencil me-1"></i> Edit Product</a>
        @endif
        <a href="{{ $returnUrl }}" class="btn btn-secondary ms-1">Back to List</a>
    </div>
</div>
@endsection

@push('scripts')
@if($product->barcode)
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
JsBarcode('#productBarcode', '{{ $product->barcode }}', {
    format: 'CODE128',
    width: 2,
    height: 70,
    displayValue: false,
    margin: 6,
});

function printBarcode() {
    const svgEl = document.getElementById('productBarcode');
    const svgData = new XMLSerializer().serializeToString(svgEl);
    const win = window.open('', '_blank', 'width=400,height=300');
    win.document.write(`<!DOCTYPE html><html><head><title>Barcode - {{ $product->sku }}</title>
    <style>body{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0;font-family:monospace;}
    p{margin:4px 0;font-size:13px;}</style></head>
    <body>${svgData}<p>{{ $product->sku }}</p><p>{{ $product->barcode }}</p><p>{{ $product->name }}</p>
    <script>window.onload=function(){window.print();}<\/script></body></html>`);
    win.document.close();
}
</script>
@endif
@endpush
