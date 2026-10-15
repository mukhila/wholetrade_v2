@extends('layouts.app')

@section('title', 'Staff Members')

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Staff Members</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Staff</li>
        </ol>
    </div>
</div>

<div class="row mb-3 align-items-center g-2">
    <div class="col-sm-5 col-md-4">
        <form action="{{ route('staff.index') }}" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" name="search"
                       placeholder="Search name, username, phone..."
                       value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit" title="Search">
                    <i class="ti ti-search"></i>
                </button>
                @if(request('search'))
                    <a href="{{ route('staff.index') }}" class="btn btn-secondary" title="Clear search">
                        <i class="ti ti-x"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
    <div class="col-sm-7 col-md-8 text-sm-end d-flex gap-2 justify-content-sm-end align-items-center flex-wrap">
        <a href="{{ route('staff-roles.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-shield me-1"></i> Staff Roles
        </a>
        <a href="{{ route('staff.create') }}" class="btn btn-primary">
            <i class="ti ti-user-plus me-1"></i> Add Staff Member
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

                {{-- Show orphaned users (created mid-process without a role) --}}
                @if(!empty($orphaned) && $orphaned->isNotEmpty())
                <div class="alert alert-warning m-3">
                    <strong><i class="ti ti-alert-triangle me-1"></i> Incomplete accounts detected:</strong>
                    The following users were created but their staff role was not saved.
                    <ul class="mb-0 mt-1">
                        @foreach($orphaned as $o)
                            <li>{{ $o->name }} — <strong>{{ $o->email }}</strong> (created {{ $o->created_at->diffForHumans() }})</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 50px;">#</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Modules Granted</th>
                                <th>Status</th>
                                <th class="text-end pe-3" style="width: 170px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $member)
                            <tr>
                                <td class="ps-3 text-muted">{{ $loop->iteration + ($staff->currentPage() - 1) * $staff->perPage() }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold text-dark">{{ $member->name }}</span>
                                    </div>
                                </td>
                                <td><code>{{ $member->username }}</code></td>
                                <td>{{ $member->phone ?? '—' }}</td>
                                <td>
                                    @if($member->staffRole)
                                        <span class="badge bg-light text-dark border">{{ $member->staffRole->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $grantedCount = $member->staffPermissions->where('can_view', true)->count();
                                    @endphp
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $grantedCount }} module(s)</span>
                                </td>
                                <td>
                                    @if($member->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('staff.show', $member->id) }}"
                                           class="btn btn-sm btn-soft-primary"
                                           title="View Profile">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('staff.permissions', $member->id) }}"
                                           class="btn btn-sm btn-soft-warning"
                                           title="Permissions">
                                            <i class="ti ti-shield"></i>
                                        </a>
                                        <a href="{{ route('staff.edit', $member->id) }}"
                                           class="btn btn-sm btn-soft-secondary"
                                           title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('staff.destroy', $member->id) }}"
                                              method="POST" style="display:inline-block;"
                                              onsubmit="return confirm('Delete this staff member? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-soft-danger"
                                                    title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="ti ti-users-off fs-24 d-block mb-2 text-muted"></i>
                                    No staff members found. <a href="{{ route('staff.create') }}">Add the first one.</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    {{ $staff->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
