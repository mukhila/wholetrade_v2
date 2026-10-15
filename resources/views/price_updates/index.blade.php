@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
                        <li class="breadcrumb-item active">Price Updates</li>
                    </ol>
                </div>
                <h4 class="page-title">Price Updates</h4>
            </div>
        </div>
    </div>

    <!-- Instructions / Info -->
    <div class="alert alert-info" role="alert">
        <strong>Info:</strong> These updates apply to Standard Prices only. Modifying prices here will update the base price list.
    </div>

    <!-- Data Grid -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="loadingIndicator" class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading Products...</p>
                    </div>

                    <div class="table-responsive d-none" id="priceTableContainer">
                        <table class="table table-centered table-sm w-100 table-bordered" id="pricesTable">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle">Code</th>
                                    <th rowspan="2" class="align-middle">Name</th>
                                    <th colspan="3" class="text-center">Current Prices (Read Only)</th>
                                    <th colspan="3" class="text-center bg-primary-subtle">New Prices (Editable)</th>
                                </tr>
                                <tr>
                                    <th class="text-end">Unit</th>
                                    <th class="text-end">Box</th>
                                    <th class="text-end">POS</th>
                                    <th class="text-end bg-primary-subtle">Unit</th>
                                    <th class="text-end bg-primary-subtle">Box</th>
                                    <th class="text-end bg-primary-subtle">POS</th>
                                </tr>
                            </thead>
                            <tbody id="priceTableBody">
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>

                    @if($canEdit)
                    <div class="mt-3 text-end">
                        <button class="btn btn-success" id="btnSave" disabled>
                            <i data-lucide="save" class="icon-sm me-1"></i> Save Price Updates
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
@vite(['resources/js/price-updates.jsx'])
@endpush
