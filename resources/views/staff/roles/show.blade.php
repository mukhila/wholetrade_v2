@extends('layouts.app')

@section('title', 'Role — ' . $staffRole->name)

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">{{ $staffRole->name }}</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff-roles.index') }}">Roles</a></li>
            <li class="breadcrumb-item active">{{ $staffRole->name }}</li>
        </ol>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Role info card -->
    <div class="col-lg-3 mb-3 mb-lg-0">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-1 fw-bold text-dark">{{ $staffRole->name }}</h5>
                @if($staffRole->description)
                    <p class="text-muted small mb-3">{{ $staffRole->description }}</p>
                @else
                    <p class="text-muted small mb-3"><em>No description provided.</em></p>
                @endif

                <div class="d-flex align-items-center mb-2 small text-muted">
                    <i class="ti ti-users fs-16 text-primary me-2"></i>
                    <span class="text-dark">{{ $staffRole->users->count() }} staff member(s)</span>
                </div>
                <div class="d-flex align-items-center mb-2 small text-muted">
                    <i class="ti ti-shield-check fs-16 text-warning me-2"></i>
                    @php $grantedModules = $permissions->filter(fn($p) => $p->can_view || $p->can_create || $p->can_edit || $p->can_delete)->count(); @endphp
                    <span class="text-dark">{{ $grantedModules }} module(s) with access</span>
                </div>
                <div class="d-flex align-items-center small text-muted">
                    <i class="ti ti-calendar fs-16 text-primary me-2"></i>
                    <span class="text-dark">Created {{ $staffRole->created_at->format('d M Y') }}</span>
                </div>
            </div>
            <div class="card-footer bg-light border-top d-flex flex-column gap-2">
                <a href="{{ route('staff-roles.permissions', $staffRole->id) }}" class="btn btn-warning btn-sm w-100">
                    <i class="ti ti-shield me-1"></i> Edit Role Permissions
                </a>
                <a href="{{ route('staff-roles.edit', $staffRole->id) }}" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="ti ti-edit me-1"></i> Edit Role Details
                </a>
                <form action="{{ route('staff-roles.destroy', $staffRole->id) }}"
                      method="POST"
                      onsubmit="return confirm('Delete this role? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-soft-danger btn-sm w-100">
                        <i class="ti ti-trash me-1"></i> Delete Role
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <!-- Permission Matrix -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="ti ti-shield-check text-warning me-1"></i>
                    Role Permissions Matrix
                </h5>
                <a href="{{ route('staff-roles.permissions', $staffRole->id) }}" class="btn btn-sm btn-outline-warning">
                    <i class="ti ti-edit me-1"></i> Edit Permissions
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Module</th>
                                <th class="text-center" style="width:90px;">View</th>
                                <th class="text-center" style="width:90px;">Create</th>
                                <th class="text-center" style="width:90px;">Edit</th>
                                <th class="text-center" style="width:90px;">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $anyPerm = false; @endphp
                            @foreach($modules as $slug => $label)
                                @php
                                    $perm = $permissions->get($slug);
                                    $hasAny = $perm && ($perm->can_view || $perm->can_create || $perm->can_edit || $perm->can_delete);
                                    if ($hasAny) $anyPerm = true;
                                @endphp
                                @if($hasAny)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $label }}</td>
                                    @foreach(['can_view','can_create','can_edit','can_delete'] as $col)
                                    <td class="text-center">
                                        @if($perm?->{$col})
                                            <span class="text-success fw-bold"><i class="ti ti-circle-check fs-18"></i></span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endif
                            @endforeach
                            @if(!$anyPerm)
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No permissions set for this role yet.
                                    <a href="{{ route('staff-roles.permissions', $staffRole->id) }}">Set permissions now.</a>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Staff members -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="ti ti-users text-primary me-1"></i>
                    Staff Members with this Role
                </h5>
                <a href="{{ route('staff.create') }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-user-plus me-1"></i> Add Staff
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Name</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th class="text-end pe-3" style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffRole->users as $member)
                            <tr>
                                <td class="ps-3 fw-semibold text-dark">{{ $member->name }}</td>
                                <td><code>{{ $member->username }}</code></td>
                                <td>{{ $member->phone ?? '—' }}</td>
                                <td>
                                    @if($member->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('staff.show', $member->id) }}" class="btn btn-soft-primary btn-sm" title="View Profile">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('staff.permissions', $member->id) }}" class="btn btn-soft-warning btn-sm" title="Individual Permissions">
                                            <i class="ti ti-shield"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No staff members assigned to this role yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
