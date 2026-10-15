@extends('layouts.app')

@push('styles')
<style>
/* Prevent theme JS from hiding form-switch toggles */
.form-check.form-switch .form-check-input {
    display: inline-block !important;
    opacity: 1 !important;
    visibility: visible !important;
}
</style>
@endpush

@section('content')

<div class="page-title-head d-flex align-items-center mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Create Product</h4>
    </div>

    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="#">Whole Trade</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
            <li class="breadcrumb-item active">Create</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Tabs -->
                    <ul class="nav nav-tabs nav-bordered mb-3">
                        <li class="nav-item">
                            <a href="#basic-tab" data-bs-toggle="tab" class="nav-link active">Basic Info</a>
                        </li>
                        <li class="nav-item">
                            <a href="#pricing-tab" data-bs-toggle="tab" class="nav-link">Pricing</a>
                        </li>
                        <li class="nav-item">
                            <a href="#inventory-tab" data-bs-toggle="tab" class="nav-link">Inventory</a>
                        </li>
                        <li class="nav-item">
                            <a href="#images-tab" data-bs-toggle="tab" class="nav-link">Images</a>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- BASIC -->
                        <div class="tab-pane fade show active" id="basic-tab">
                           <div class="row g-3">
                                        <div class="col-md-6 mb-3">
                                            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="sku" name="sku" value="{{ $sku }}" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="product_type" class="form-label">Product Type <span class="text-danger">*</span></label>
                                            <select class="form-select" id="product_type" name="product_type" required>
                                                <option value="STANDARD" {{ old('product_type') == 'STANDARD' ? 'selected' : '' }}>Standard</option>
                                                <option value="VARIANT" {{ old('product_type') == 'VARIANT' ? 'selected' : '' }}>Variant</option>
                                                <option value="COMPOSITE" {{ old('product_type') == 'COMPOSITE' ? 'selected' : '' }}>Composite</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="category_id" class="form-label">Category</label>
                                            <select class="form-select" id="category_id" name="category_id">
                                                <option value="">Select Category</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="brand_id" class="form-label">Brand</label>
                                            <select class="form-select" id="brand_id" name="brand_id">
                                                <option value="">Select Brand</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="supplier_id" class="form-label">Supplier</label>
                                            <select class="form-select" id="supplier_id" name="supplier_id">
                                                <option value="">Select Supplier</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="uom_id" class="form-label">UOM (Unit of Measure)</label>
                                            <select class="form-select" id="uom_id" name="uom_id">
                                                <option value="">Select UOM</option>
                                                @foreach($uoms as $uom)
                                                    <option value="{{ $uom->id }}" {{ old('uom_id') == $uom->id ? 'selected' : '' }}>{{ $uom->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="barcode" class="form-label">Barcode</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="barcode" name="barcode" value="{{ old('barcode', $predictedBarcode) }}">
                                                <button type="button" class="btn btn-outline-secondary" id="regenBarcode" title="Regenerate barcode">
                                                    <i class="ti ti-refresh"></i>
                                                </button>
                                            </div>
                                            <div class="mt-2 text-center" id="barcodePreview" style="background:#fff;padding:8px;border-radius:6px;border:1px solid #dee2e6;">
                                                <svg id="barcodeSvg"></svg>
                                                <div class="text-muted" style="font-size:.7rem;" id="barcodeValue"></div>
                                            </div>
                                        </div>
                                         
                                        <div class="col-md-4 mb-3">
                                            <label for="expiry_date" class="form-label">Expiry Date <small class="text-muted">(optional)</small></label>
                                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="weight" class="form-label">Weight</label>
                                            <input type="number" step="0.01" class="form-control" id="weight" name="weight" value="{{ old('weight') }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="pack_size_id" class="form-label">Pack Size Unit</label>
                                            <select class="form-select" id="pack_size_id" name="pack_size_id">
                                                <option value="">Select Size Unit</option>
                                                @foreach($sizes as $size)
                                                    <option value="{{ $size->id }}" {{ old('pack_size_id') == $size->id ? 'selected' : '' }}>{{ $size->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="pack_size_qty" class="form-label">Pack Size Qty</label>
                                            <input type="number" class="form-control" id="pack_size_qty" name="pack_size_qty" value="{{ old('pack_size_qty', 1) }}">
                                        </div>

                                        <div class="col-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" value="1" checked>
                                                <label class="form-check-label" for="status">Active</label>
                                            </div>
                                        </div>
                                    </div>
                        </div>

                        <!-- PRICING -->
                        <div class="tab-pane fade" id="pricing-tab">
                            <div class="row g-3">
                                <h5 class="mb-3">Cost Price</h5>
                                        <div class="col-md-6 mb-3">
                                            <label for="cost_price_item" class="form-label">Cost Price (Per Item)</label>
                                            <input type="number" step="0.01" class="form-control" id="cost_price_item" name="cost_price_item" value="{{ old('cost_price_item') }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cost_price_box" class="form-label">Cost Price (Per Box)</label>
                                            <input type="number" step="0.01" class="form-control" id="cost_price_box" name="cost_price_box" value="{{ old('cost_price_box') }}">
                                        </div>

                                        <h5 class="mb-3 mt-2">Margin</h5>
                                        <div class="col-md-6 mb-3">
                                            <label for="margin_type" class="form-label">Margin Type</label>
                                            <select class="form-select" id="margin_type" name="margin_type">
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="fixed">Fixed Amount</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="margin" class="form-label">Margin Value</label>
                                            <input type="number" step="0.01" class="form-control" id="margin" name="margin" value="{{ old('margin') }}">
                                        </div>

                                        <h5 class="mb-3 mt-2">Tax</h5>
                                        <div class="col-md-6 mb-3">
                                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                            <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', 0) }}" min="0" max="100">
                                        </div>

                                        <h5 class="mb-3 mt-2">Sales Price</h5>
                                        <div class="col-md-6 mb-3">
                                            <label for="sales_price_item" class="form-label">Sales Price (Per Item)</label>
                                            <input type="number" step="0.01" class="form-control" id="sales_price_item" name="sales_price_item" value="{{ old('sales_price_item') }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="sales_price_box" class="form-label">Sales Price (Per Box)</label>
                                            <input type="number" step="0.01" class="form-control" id="sales_price_box" name="sales_price_box" value="{{ old('sales_price_box') }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="min_sales_price_item" class="form-label">Min Sales Price (Per Item)</label>
                                            <input type="number" step="0.01" class="form-control" id="min_sales_price_item" name="min_sales_price_item" value="{{ old('min_sales_price_item') }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="min_sales_price_box" class="form-label">Min Sales Price (Per Box)</label>
                                            <input type="number" step="0.01" class="form-control" id="min_sales_price_box" name="min_sales_price_box" value="{{ old('min_sales_price_box') }}">
                                        </div>
                                        
                                        <h5 class="mb-3 mt-2">Retail Price</h5>
                                        <div class="col-md-6 mb-3">
                                            <label for="retail_price_item" class="form-label">Retail Price (Per Item)</label>
                                            <input type="number" step="0.01" class="form-control" id="retail_price_item" name="retail_price_item" value="{{ old('retail_price_item') }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="retail_price_box" class="form-label">Retail Price (Per Box)</label>
                                            <input type="number" step="0.01" class="form-control" id="retail_price_box" name="retail_price_box" value="{{ old('retail_price_box') }}">
                                        </div>
                            </div>
                        </div>

                        <!-- INVENTORY -->
                        <div class="tab-pane fade" id="inventory-tab">
                            <div class="row g-3">
                                   <div class="col-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="inventory_control" name="inventory_control" value="1" checked>
                                                <label class="form-check-label" for="inventory_control">Enable Inventory Control</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="min_stock_qty" class="form-label">Min Stock Quantity</label>
                                            <input type="number" class="form-control" id="min_stock_qty" name="min_stock_qty" value="{{ old('min_stock_qty', 0) }}">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="stock_in_hand" class="form-label">Total Stock (Initial)</label>
                                            <input type="number" class="form-control" id="stock_in_hand" name="stock_in_hand" value="{{ old('stock_in_hand', 0) }}" min="0">
                                            <small class="text-muted">Set initial stock level</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="stock_qty_pcs" class="form-label">Stock Qty (PCS)</label>
                                            <input type="number" step="0.01" class="form-control" id="stock_qty_pcs" name="stock_qty_pcs" value="{{ old('stock_qty_pcs', 0) }}">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="stock_qty_box" class="form-label">Stock Qty (Box)</label>
                                            <input type="number" step="0.01" class="form-control" id="stock_qty_box" name="stock_qty_box" value="{{ old('stock_qty_box', 0) }}">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="reorder_qty" class="form-label">Reorder Quantity</label>
                                            <input type="number" class="form-control" id="reorder_qty" name="reorder_qty" value="{{ old('reorder_qty', 0) }}">
                                        </div>

                                        <h5 class="mb-3 mt-2">Batch & Serial</h5>
                                        <div class="col-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="have_batch" name="have_batch" value="1">
                                                <label class="form-check-label" for="have_batch">Have Batch</label>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="have_serial" name="have_serial" value="1">
                                                <label class="form-check-label" for="have_serial">Have Serial</label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="lot_no" class="form-label">Lot Number (Initial)</label>
                                            <input type="text" class="form-control" id="lot_no" name="lot_no" value="{{ old('lot_no') }}">
                                        </div>
                            </div>
                        </div>

                        <!-- IMAGES -->
                        <div class="tab-pane fade" id="images-tab">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Product Image <small class="text-muted">(JPG, PNG, WebP – max 2 MB)</small></label>
                                    <input type="file" class="form-control" name="image" id="imageUpload" accept="image/*">
                                    <div id="imagePreviews" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Create Product</button>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
// Barcode live preview
const barcodeInput = document.getElementById('barcode');
const barcodeSvg   = document.getElementById('barcodeSvg');
const barcodeValue = document.getElementById('barcodeValue');
const predictedBarcode = '{{ $predictedBarcode }}';

function renderBarcode(value) {
    if (!value) { barcodeSvg.innerHTML = ''; barcodeValue.textContent = ''; return; }
    try {
        JsBarcode(barcodeSvg, value, {
            format: 'CODE128',
            width: 2,
            height: 60,
            displayValue: false,
            margin: 4,
        });
        barcodeValue.textContent = value;
    } catch(e) {
        barcodeSvg.innerHTML = '';
        barcodeValue.textContent = 'Invalid barcode';
    }
}

barcodeInput.addEventListener('input', function () { renderBarcode(this.value.trim()); });

document.getElementById('regenBarcode').addEventListener('click', function () {
    barcodeInput.value = predictedBarcode;
    renderBarcode(predictedBarcode);
});

// Render on page load with pre-filled value
renderBarcode(barcodeInput.value.trim());

document.getElementById('imageUpload').addEventListener('change', function () {
    const container = document.getElementById('imagePreviews');
    container.innerHTML = '';
    if (!this.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        container.innerHTML = `<img src="${e.target.result}" style="width:100px;height:100px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">`;
    };
    reader.readAsDataURL(this.files[0]);
});
</script>
@endpush
