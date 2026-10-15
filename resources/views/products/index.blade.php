@extends('layouts.app')

@section('content')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush


		
		<div class="page-title-head d-flex align-items-center ">
			<div class="flex-grow-1">
				<h4 class="fs-xl fw-bold m-0">Products</h4>
			</div>

			<div class="text-end">
				<ol class="breadcrumb m-0 py-0">
					<li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
					<li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
					<li class="breadcrumb-item active">Products</li>
				</ol>
			</div>
		</div>

        <div class="row mb-2">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right d-flex gap-2 flex-wrap">
                        <a href="{{ route('products.export') }}" class="btn btn-success"><i class="ti ti-download me-1"></i> Export</a>
                        @if($canCreate)
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#importModal"><i class="ti ti-upload me-1"></i> Import</button>
                        <a href="{{ route('products.create') }}" class="btn btn-primary">Create New</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Import Modal --}}
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Import Products</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <p class="text-muted small mb-3">Upload a CSV to bulk-import products. Category and Brand are matched by name — make sure they already exist in the system.</p>
                            <div class="mb-3">
                                <label for="csv_file" class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.txt" required>
                            </div>
                            <a href="{{ route('products.import.template') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-file-download me-1"></i> Download Template</a>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning"><i class="ti ti-upload me-1"></i> Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('products.index') }}" class="row g-2 mb-4">
                            <div class="col-md-2">
                                <select class="form-select" name="category_id">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="brand_id">
                                    <option value="">All Brands</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by Name or SKU..." value="{{ request('search') }}">
                                    @if(request()->anyFilled(['search', 'category_id', 'brand_id', 'status']))
                                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary" title="Clear Filters"><i class="ti ti-x"></i></a>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-search me-1"></i> Search</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:60px;"></th>
                                        <th>SKU</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Stock (PCS)</th>
                                        <th>Stock (Box)</th>
                                        <th>Cost Price</th>
                                        <th>Selling Price</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                    <tr id="product-{{ $product->id }}">
                                        <td>
                                            @if($product->image)
                                              <img src="{{ \Storage::disk('public_uploads')->url($product->image->image_path) }}"
										     alt="{{ $product->name }}"
     style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;">
                                            @else
                                                <div style="width:48px;height:48px;border-radius:6px;border:1px solid #dee2e6;background:#f8f9fa;display:flex;align-items:center;justify-content:center;">
                                                    <i class="ti ti-photo text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $product->sku }}</td>
                                        <td>{{ $product->name }}</td>
                                        <td><span class="badge bg-info">{{ $product->product_type }}</span></td>
                                        <td>{{ $product->category->name ?? '-' }}</td>
                                        <td>{{ $product->brand->name ?? '-' }}</td>
                                        <td>{{ number_format($product->stock_qty_pcs, 2) }}</td>
                                        <td>{{ number_format($product->stock_qty_box, 2) }}</td>
                                        <td>{{ $product->price?->cost_price_item ? number_format($product->price->cost_price_item, 2) : '-' }}</td>
                                        <td>{{ $product->price?->sales_price_item ? number_format($product->price->sales_price_item, 2) : '-' }}</td>
                                        <td>
                                            @if($product->status)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('products.show', ['product' => $product->id, 'return_url' => request()->fullUrl() . '#product-' . $product->id]) }}" class="action-icon btn btn-info bg-gradient btn-icon"><i class = "ti ti-eye"></i></a>
                                            @if($canEdit)<a href="{{ route('products.edit', ['product' => $product->id, 'return_url' => request()->fullUrl() . '#product-' . $product->id]) }}" class="action-icon btn btn-secondary bg-gradient btn-icon"><i class = "ti ti-pencil"></i></a>@endif
                                            @if($canEdit)
                                            <form action="{{ route('products.toggle-status', $product->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('PATCH')
                                                @if($product->status)
                                                    <button type="submit" class="action-icon btn btn-warning bg-gradient btn-icon" title="Set Inactive"><i class="ti ti-toggle-right"></i></button>
                                                @else
                                                    <button type="submit" class="action-icon btn btn-success bg-gradient btn-icon" title="Set Active"><i class="ti ti-toggle-left"></i></button>
                                                @endif
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                       <div class="mt-4 d-flex justify-content-center">
    {{ $products->withQueryString()->links() }}
</div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('select[name="category_id"]').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Category',
                allowClear: true,
                width: '100%'
            });
            $('select[name="brand_id"]').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Brand',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endpush

@push('styles')
<style>
tr:target {
    scroll-margin-top: 150px;
}
tr:target td {
    animation: highlight-row 2s ease-out;
}
@keyframes highlight-row {
    0% {
        background-color: rgba(138, 184, 68, 0.3) !important;
    }
    100% {
        background-color: transparent;
    }
}
</style>
@endpush
