@extends('layouts.app')

@section('title', 'Staff Roles')

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Staff Roles</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
            <li class="breadcrumb-item active">Roles</li>
        </ol>
    </div>
</div>

<div class="row mb-3 align-items-center">
    <div class="col-12 text-sm-end d-flex gap-2 justify-content-sm-end align-items-center">
        <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-users me-1"></i> Staff Members
        </a>
        <a href="{{ route('staff-roles.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Add Role
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 50px;">#</th>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th>Staff Count</th>
                                <th class="text-end pe-3" style="width: 170px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                            <tr>
                                <td class="ps-3 text-muted">{{ $loop->iteration + ($roles->currentPage() - 1) * $roles->perPage() }}</td>
                                <td class="fw-semibold text-dark">{{ $role->name }}</td>
                                <td class="text-muted">{{ $role->description ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $role->users_count }} member(s)</span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('staff-roles.show', $role->id) }}"
                                           class="btn btn-sm btn-soft-primary"
                                           title="View Role">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('staff-roles.permissions', $role->id) }}"
                                           class="btn btn-sm btn-soft-warning"
                                           title="Role Permissions">
                                            <i class="ti ti-shield"></i>
                                        </a>
                                        <a href="{{ route('staff-roles.edit', $role->id) }}"
                                           class="btn btn-sm btn-soft-secondary"
                                           title="Edit Role">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('staff-roles.destroy', $role->id) }}"
                                              method="POST" style="display:inline-block;"
                                              onsubmit="return confirm('Delete this role? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-soft-danger"
                                                    title="Delete Role">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="ti ti-shield-off fs-24 d-block mb-2 text-muted"></i>
                                    No staff roles yet. <a href="{{ route('staff-roles.create') }}">Create the first one.</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    {{ $roles->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
