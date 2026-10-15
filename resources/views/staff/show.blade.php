@extends('layouts.app')

@section('title', 'Staff Profile — ' . $staff->name)

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Staff Profile</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
            <li class="breadcrumb-item active">{{ $staff->name }}</li>
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
    <!-- Profile Card -->
    <div class="col-lg-4 mb-3 mb-lg-0">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-4">
                <div class="mx-auto mb-3" style="width: 80px; height: 80px;">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold fs-2 w-100 h-100">
                        {{ strtoupper(substr($staff->name, 0, 1)) }}
                    </div>
                </div>
                <h5 class="mb-1 fw-bold text-dark">{{ $staff->name }}</h5>
                <p class="text-muted mb-2">{{ $staff->staffRole?->name ?? 'Staff Member' }}</p>
                <div>
                    @if($staff->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">Active</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1">Inactive</span>
                    @endif
                </div>

                <div class="text-start mt-4 border-top pt-3">
                    <div class="d-flex align-items-center mb-2 text-muted">
                        <i class="ti ti-at fs-16 text-primary me-2"></i>
                        <span class="text-dark fw-medium">{{ $staff->username }}</span>
                    </div>
                    @if($staff->email)
                    <div class="d-flex align-items-center mb-2 text-muted">
                        <i class="ti ti-mail fs-16 text-primary me-2"></i>
                        <span class="text-dark">{{ $staff->email }}</span>
                    </div>
                    @endif
                    <div class="d-flex align-items-center mb-2 text-muted">
                        <i class="ti ti-phone fs-16 text-primary me-2"></i>
                        <span class="text-dark">{{ $staff->phone ?? 'Not set' }}</span>
                    </div>
                    <div class="d-flex align-items-center text-muted">
                        <i class="ti ti-calendar fs-16 text-primary me-2"></i>
                        <span class="text-dark">Joined {{ $staff->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light border-top d-flex gap-2">
                <a href="{{ route('staff.edit', $staff->id) }}" class="btn btn-outline-secondary btn-sm flex-grow-1">
                    <i class="ti ti-edit me-1"></i> Edit
                </a>
                <a href="{{ route('staff.permissions', $staff->id) }}" class="btn btn-warning btn-sm flex-grow-1">
                    <i class="ti ti-shield me-1"></i> Permissions
                </a>
            </div>
        </div>
    </div>

    <!-- Permissions Summary -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <div>
                    <h5 class="card-title mb-1 fw-bold">Effective Permissions</h5>
                    @if($staff->staffRole)
                    <small class="text-muted">
                        <i class="ti ti-shield me-1 text-warning"></i>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ $staff->staffRole->name }}</span> role permissions
                        + individual overrides
                    </small>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if($staff->staffRole)
                    <a href="{{ route('staff-roles.permissions', $staff->staffRole->id) }}" class="btn btn-sm btn-outline-warning" title="Edit Role Permissions">
                        <i class="ti ti-shield me-1"></i> Role Perms
                    </a>
                    @endif
                    <a href="{{ route('staff.permissions', $staff->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-user-check me-1"></i> Individual Perms
                    </a>
                </div>
            </div>

            @php
                $staffRole = $staff->staffRole;
                $rolePerms = $staffRole ? $staffRole->modulePermissions->keyBy('module') : collect();
            @endphp

            @if($staffRole && $rolePerms->isNotEmpty())
            <div class="px-3 pt-3 pb-2 bg-light border-bottom">
                <div class="d-flex gap-3 flex-wrap align-items-center small">
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="ti ti-shield me-1"></i> From role</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="ti ti-user me-1"></i> Individual override</span>
                    <span class="text-muted">Role permissions apply to all staff with <strong>{{ $staffRole->name }}</strong></span>
                </div>
            </div>
            @endif

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Module</th>
                                <th class="text-center" style="width:80px;">View</th>
                                <th class="text-center" style="width:80px;">Create</th>
                                <th class="text-center" style="width:80px;">Edit</th>
                                <th class="text-center" style="width:80px;">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $anyShown = false; @endphp
                            @foreach($modules as $slug => $label)
                                @php
                                    $userPerm = $permissions->get($slug);
                                    $rolePerm = $rolePerms->get($slug);

                                    $effective = [
                                        'view'   => ($rolePerm?->can_view   || $userPerm?->can_view),
                                        'create' => ($rolePerm?->can_create || $userPerm?->can_create),
                                        'edit'   => ($rolePerm?->can_edit   || $userPerm?->can_edit),
                                        'delete' => ($rolePerm?->can_delete || $userPerm?->can_delete),
                                    ];

                                    $hasAny = in_array(true, $effective);
                                    if ($hasAny) $anyShown = true;
                                @endphp
                                @if($hasAny)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $label }}</td>
                                    @foreach(['view','create','edit','delete'] as $action)
                                    @php
                                        $fromRole = $rolePerm?->{"can_{$action}"};
                                        $fromUser = $userPerm?->{"can_{$action}"};
                                    @endphp
                                    <td class="text-center">
                                        @if($fromRole && $fromUser)
                                            <span class="text-success" title="Role + Individual"><i class="ti ti-circle-check fs-18"></i></span>
                                        @elseif($fromRole)
                                            <span class="text-warning" title="From Role: {{ $staffRole->name }}"><i class="ti ti-shield fs-18"></i></span>
                                        @elseif($fromUser)
                                            <span class="text-primary" title="Individual permission"><i class="ti ti-user-check fs-18"></i></span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endif
                            @endforeach
                            @if(!$anyShown)
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No permissions assigned yet.
                                        @if($staff->staffRole)
                                            <a href="{{ route('staff-roles.permissions', $staff->staffRole->id) }}">Set role permissions</a> or
                                        @endif
                                        <a href="{{ route('staff.permissions', $staff->id) }}">set individual permissions</a>.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer py-2 px-3 bg-light border-top small text-muted">
                <span class="me-3"><i class="ti ti-shield text-warning me-1"></i> <strong>Yellow shield</strong> = from role</span>
                <span class="me-3"><i class="ti ti-user-check text-primary me-1"></i> <strong>Blue user</strong> = individual only</span>
                <span><i class="ti ti-circle-check text-success me-1"></i> <strong>Green check</strong> = both sources</span>
            </div>
        </div>
    </div>
</div>
@endsection
