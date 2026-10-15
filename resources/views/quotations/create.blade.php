@extends('layouts.app')

@section('content')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

    <div class="row mt-2">
        <div class="col-12">
            <div class="page-title-box">
                  <h4 class="page-title">New Sales Quotation</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('quotations.index') }}">Quotations</a></li>
                        <li class="breadcrumb-item active">New Quotation</li>
                    </ol>
                </div>
              
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <!-- LEFT: Product Browser -->
        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <select class="form-select" id="filterCategory">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterBrand">
                                <option value="">All Brands</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchProduct" placeholder="Search product name or SKU...">
                                <button class="btn btn-primary" type="button" id="btnSearch">
                                    <i class="ti ti-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div id="loadingProducts" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    
                    <div id="productsGrid" class="row g-3">
                        <!-- Products will be loaded here via JS -->
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3 d-flex justify-content-center" id="paginationContainer">
                        <!-- JS Pagination -->
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Cart & Checkout -->
        <div class="col-xl-4 col-lg-5">
            <div class="card position-sticky" style="top: 80px;">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Current Quotation</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-nowrap table-centered mb-0" id="cartTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Product</th>
                                    <th style="width: 80px;">Qty</th>
                                    <th class="text-end">Total</th>
                                    <th style="width: 30px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartBody">
                                <tr id="emptyCartRow">
                                    <td colspan="4" class="text-center text-muted py-3">Cart is empty</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Tax:</span>
                            <span class="text-muted" id="cartTaxDisplay">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Total:</h4>
                            <h4 class="mb-0 text-primary" id="cartTotalDisplay">0.00</h4>
                        </div>

                        <form id="quotationForm">
                            <div class="mb-3">
                                <label for="customerId" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="customerId" required>
                                    <option value="">Select Customer...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" rows="2" placeholder="Optional notes..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success w-100 btn-lg shadow-sm" id="btnSubmit">
                                <i class="ti ti-check me-1"></i> Submit Quotation
                            </button>
                        </form>
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
            $('#customerId').select2({
                placeholder: "Select Customer...",
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5'
            });
        });
    </script>
    @vite(['resources/js/quotations-blade.jsx'])
@endpush
