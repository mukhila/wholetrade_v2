@extends('layouts.app')

@section('content')
<!-- Page Title -->
<div class="row mt-3">
    <div class="col-12">
        <div class="page-title-box d-flex justify-content-between align-items-center">
            <h4 class="page-title">Products Catalogue</h4>
             <div class="page-title-right text-end">
            <a href="{{ route('cart.index') }}" class="btn btn-warning position-relative">
                <i data-lucide="shopping-cart" class="icon-sm me-1"></i> View Cart
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ count((array) session('cart')) }}
                </span>
            </a>
                </div>
        </div>
    </div>
</div>

<!-- Products Grid -->
<div class="row mt-3">
    @forelse($products as $product)
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card product-box">
            <div class="card-body">
                <div class="bg-light p-3 rounded-2 mb-3 position-relative">
                    @if($product->image)
                        <img src="{{ \Storage::disk('public_uploads')->url($product->image->image_path) }}"
                             alt="{{ $product->name }}"
                             class="img-fluid mx-auto d-block"
                             style="height:180px;object-fit:cover;border-radius:6px;">
                    @else
                        <div class="d-flex align-items-center justify-content-center" style="height: 180px;">
                            <i data-lucide="image" class="text-muted fs-32" style="width: 64px; height: 64px;"></i>
                        </div>
                    @endif
                    <!-- Badge Logic if needed -->
                    @if($product->product_type === 'COMPOSITE')
                        <span class="badge bg-info position-absolute top-0 start-0 m-2">Combo</span>
                    @endif
                </div>

                <h5 class="font-16 text-truncate"><a href="#" class="text-dark">{{ $product->name }}</a></h5>
                <p class="text-muted mb-1 text-truncate">{{ $product->description }}</p>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h4 class="m-0 fw-semibold text-dark">
                        @if($product->price)
                            ${{ number_format($product->price->sales_price_item ?? 0, 2) }}
                        @else
                            <span class="text-muted fs-14">Price N/A</span>
                        @endif
                    </h4>
                    <a href="{{ route('cart.add', $product->id) }}" class="btn btn-sm btn-primary">
                        <i data-lucide="shopping-cart" class="icon-xs me-1"></i> Add
                    </a>
                </div>
                
                <div class="row mt-3">
                    <div class="col">
                         <span class="badge bg-light text-dark border">{{ $product->sku }}</span>
                    </div>
                    <div class="col-auto">
                        <small class="text-muted">Stock: {{ $product->inventory->stock_in_hand ?? 0 }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-warning" role="alert">
            No products found.
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
<div class="row">
    <div class="col-12">
        {{ $products->links() }}
    </div>
</div>
@endsection
