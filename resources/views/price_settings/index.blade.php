@extends('layouts.app')

@section('content')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
                        <li class="breadcrumb-item active">Price Settings</li>
                    </ol>
                </div>
                <h4 class="page-title">Bulk Price Settings</h4>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Pricing Type</label>
                            <select class="form-select" id="priceType">
                                <option value="STANDARD">Standard Price</option>
                                <option value="ASSOCIATE">Associate Price</option>
                                <option value="GROUP">Group Price</option>
                                <option value="PROMOTION">Promotion</option>
                            </select>
                        </div>

                        <div class="col-md-3" id="associateFilter" style="display:none;">
                            <label class="form-label">Associate</label>
                            <select class="form-select" id="associateId">
                                <option value="">Select Associate</option>
                                @foreach($associates as $associate)
                                    <option value="{{ $associate->id }}">{{ $associate->name }} ({{ $associate->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3" id="groupFilter" style="display:none;">
                            <label class="form-label">Associate Group</label>
                            <select class="form-select" id="groupId">
                                <option value="">Select Group</option>
                                @foreach($associateGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="categoryId">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Brand</label>
                            <select class="form-select" id="brandId">
                                <option value="">All Brands</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button class="btn btn-primary flex-grow-1" id="btnFilter">
                                <i data-lucide="filter" class="icon-sm me-1"></i> Filter
                            </button>
                            <button class="btn btn-light" id="btnReset" title="Reset Filters">
                                <i data-lucide="refresh-cw" class="icon-sm"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Grid -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="loadingIndicator" class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading Prices...</p>
                    </div>

                    <div class="table-responsive d-none" id="priceTableContainer">
                        <table class="table table-centered table-sm w-100" id="pricesTable">
                            <thead class="table-light">
                                <tr id="tableHeaders">
                                    <!-- Populated via JS -->
                                </tr>
                            </thead>
                            <tbody id="priceTableBody">
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>

                    <div id="paginationWrapper"></div>

                    @if($canEdit)
                    <div class="mt-3 text-end">
                        <button class="btn btn-success" id="btnSave">
                            <i data-lucide="save" class="icon-sm me-1"></i> Save All Changes
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@vite(['resources/js/price-settings.jsx'])
@endpush
