@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Admin</a></li>
                        <li class="breadcrumb-item active">Invoice Management</li>
                    </ol>
                </div>
                <h4 class="page-title">Invoice Management</h4>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row">
        <div class="col-md-4">
            <div class="card bg-warning-subtle text-warning shadow-none">
                <div class="card-body">
                    <h5 class="mt-0 mb-1 text-warning">Pending Payment</h5>
                    <h3 class="mb-0">{{ $pending }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info-subtle text-info shadow-none">
                <div class="card-body">
                    <h5 class="mt-0 mb-1 text-info">Partially Paid</h5>
                    <h3 class="mb-0">{{ $partiallyPaid }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle text-success shadow-none">
                <div class="card-body">
                    <h5 class="mt-0 mb-1 text-success">Paid</h5>
                    <h3 class="mb-0">{{ $paid }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">All Invoices</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Quotation Ref</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $inv)
                                    <tr>
                                        <td class="fw-bold">{{ $inv->invoice_number }}</td>
                                        <td>{{ $inv->quotation->quotation_number }}</td>
                                        <td>{{ $inv->quotation->customer->name ?? 'Unknown' }}</td>
                                        <td>{{ number_format($inv->grand_total, 2) }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm dropdown-toggle
                                                    @if($inv->payment_status == 'Paid') btn-success
                                                    @elseif($inv->payment_status == 'Partially Paid') btn-info
                                                    @elseif($inv->payment_status == 'Cancelled') btn-danger
                                                    @else btn-warning @endif" data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ $inv->payment_status }} <i class="mdi mdi-chevron-down"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item status-action" href="#" data-id="{{ $inv->id }}" data-status="Pending">Pending</a></li>
                                                    <li><a class="dropdown-item status-action" href="#" data-id="{{ $inv->id }}" data-status="Partially Paid">Partially Paid</a></li>
                                                    <li><a class="dropdown-item status-action" href="#" data-id="{{ $inv->id }}" data-status="Paid">Paid</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item status-action link-danger" href="#" data-id="{{ $inv->id }}" data-status="Cancelled">Cancelled</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>{{ $inv->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-light btn-pdf" data-id="{{ $inv->id }}"><i class="ti ti-file-text"></i> PDF</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-4">No invoices found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script for Actions -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Status Update
            document.querySelectorAll('.status-action').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    let id = this.dataset.id;
                    let status = this.dataset.status;

                    if(!confirm(`Change status to ${status}?`)) return;

                    axios.put(`/api/admin/invoices/${id}/status`, { status: status })
                        .then(res => {
                            // alert(res.data.message);
                            window.location.reload();
                        })
                        .catch(err => alert('Error: ' + err.response.data.message));
                });
            });

            // PDF Generation
            document.querySelectorAll('.btn-pdf').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    window.open(`/api/admin/invoices/${id}/export`, '_blank');
                });
            });
        });
    </script>
@endsection
