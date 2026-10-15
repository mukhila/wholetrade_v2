@extends('layouts.app')

@section('content')

        <div class="page-title-head d-flex align-items-center">
            <div class="flex-grow-1">
                <h4 class="fs-xl fw-bold m-0">Associate Details</h4>
            </div>
            <div class="text-end">
                <ol class="breadcrumb m-0 py-0">
                    <li class="breadcrumb-item"><a href="{{ route('associates.index') }}">Associates</a></li>
                    <li class="breadcrumb-item active">View Details</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">

                        {{-- ── Row 1: Basic Info + Contact Info ── --}}
                        <div class="row mb-4">

                            {{-- Basic Information --}}
                            <div class="col-md-6">
                                <h4 class="header-title mb-3">Basic Information</h4>
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th style="width: 35%;">Code</th>
                                            <td>{{ $associate->code }}</td>
                                        </tr>
                                        <tr>
                                            <th>Name</th>
                                            <td>{{ $associate->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Type</th>
                                            <td>
                                                <span class="badge bg-{{ $associate->type == 'Customer' ? 'info' : 'warning' }}">
                                                    {{ $associate->type }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Username</th>
                                            <td>{{ $associate->username ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @if($associate->status)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Parent Associate</th>
                                            <td>
                                                @if($associate->parent)
                                                    {{ $associate->parent->name }} ({{ $associate->parent->code }})
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Associate Group</th>
                                            <td>{{ $associate->associateGroup->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Assigned Salesman</th>
                                            <td>{{ $associate->salesman->name ?? '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Contact Information --}}
                            <div class="col-md-6">
                                <h4 class="header-title mb-3">Contact Information</h4>
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th style="width: 35%;">Email</th>
                                            <td>{{ $associate->email ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Phone</th>
                                            <td>{{ $associate->phone ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Alternate Phone</th>
                                            <td>{{ $associate->alternate_phone ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Contact Person</th>
                                            <td>{{ $associate->contact_person ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Website</th>
                                            <td>
                                                @if($associate->website)
                                                    <a href="{{ $associate->website }}" target="_blank">{{ $associate->website }}</a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Address Line 1</th>
                                            <td>{{ $associate->address_line_1 ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Address Line 2</th>
                                            <td>{{ $associate->address_line_2 ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Postal Code</th>
                                            <td>{{ $associate->postal_code ?? '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ── Row 2: Financial & GST Information ── --}}
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4 class="header-title mb-3">Financial Information</h4>
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th style="width: 35%;">Currency</th>
                                            <td>
                                                @if($associate->currency)
                                                    {{ $associate->currency->code }} — {{ $associate->currency->name }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Credit Limit</th>
                                            <td>
                                                @if($associate->credit_limit !== null)
                                                    {{ number_format($associate->credit_limit, 2) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tax</th>
                                            <td>
                                                @if($associate->tax)
                                                    {{ $associate->tax->name }} ({{ $associate->tax->percentage }}%)
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>GST Option</th>
                                            <td>
                                                @php
                                                    $gstLabels = [
                                                        'E' => ['label' => 'GST Exclusive', 'class' => 'bg-success'],
                                                        'I' => ['label' => 'GST Inclusive', 'class' => 'bg-info'],
                                                        'N' => ['label' => 'No GST',        'class' => 'bg-secondary'],
                                                    ];
                                                    $gst = $gstLabels[$associate->gst_option] ?? ['label' => '-', 'class' => 'bg-light text-dark'];
                                                @endphp
                                                <span class="badge {{ $gst['class'] }}">{{ $gst['label'] }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            @if($canEdit)
                            <a href="{{ route('associates.edit', $associate->id) }}" class="btn btn-primary">
                                <i data-lucide="pencil" class="icon-sm me-1"></i> Edit
                            </a>
                            @endif
                            <a href="{{ route('associates.index') }}" class="btn btn-light">Back to List</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>

@endsection
