@extends('layouts.app')

@section('title', 'Permissions — ' . $staff->name)

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Individual Permissions &mdash; {{ $staff->name }}</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.show', $staff->id) }}">{{ $staff->name }}</a></li>
            <li class="breadcrumb-item active">Permissions</li>
        </ol>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($staff->staffRole)
<div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
    <i class="ti ti-shield fs-20 mt-1 flex-shrink-0 text-warning"></i>
    <div>
        <strong>{{ $staff->name }}</strong> is assigned to the
        <a href="{{ route('staff-roles.permissions', $staff->staffRole->id) }}" class="fw-bold text-dark text-decoration-underline">{{ $staff->staffRole->name }}</a> role.
        Permissions already granted by the role are indicated with a
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="ti ti-shield me-1"></i> Role</span> badge &mdash; they are automatically active.
        Use the checkboxes below to grant <em>additional</em> individual permissions.
    </div>
</div>
@else
<div class="alert alert-info d-flex align-items-center gap-2 mb-3">
    <i class="ti ti-info-circle fs-20 flex-shrink-0 text-info"></i>
    <div>
        This staff member has no role assigned. Tick the actions they may perform per module.
        Unticked modules are completely hidden from their menu.
    </div>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Individual Module Access</h5>
                        <p class="text-muted mb-0 small">
                            Checkboxes here grant <strong>additional</strong> access beyond the role.
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1"><i class="ti ti-shield"></i> Yellow</span> = granted by role.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" id="grantAllView">
                            <i class="ti ti-eye me-1"></i> Grant All View
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="revokeAll">
                            <i class="ti ti-x me-1"></i> Revoke All Individual
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <form action="{{ route('staff.permissions.update', $staff->id) }}" method="POST" id="permissionsForm">
                    @csrf

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0" id="permissionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="min-width:200px;">Module</th>
                                    @foreach(['view' => 'View / Access', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete', 'export' => 'Export', 'import' => 'Import'] as $col => $label)
                                    <th class="text-center" style="width:110px;">
                                        <div class="small fw-bold">{{ $label }}</div>
                                        <div class="form-check d-flex justify-content-center mt-1">
                                            <input class="form-check-input col-toggle" type="checkbox"
                                                   data-col="{{ $col }}" title="Toggle all {{ $label }}">
                                        </div>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $slug => $moduleLabel)
                                    @php
                                        $perm     = $permissions->get($slug);
                                        $rolePerm = $rolePerms->get($slug);
                                    @endphp
                                    <tr class="permission-row" data-module="{{ $slug }}">
                                        <td class="ps-3 fw-medium align-middle">{{ $moduleLabel }}</td>

                                        @foreach(['view','create','edit','delete','export','import'] as $action)
                                            @php
                                                $fromRole = $rolePerm?->{"can_{$action}"} ?? false;
                                                $fromUser = $perm    ?->{"can_{$action}"} ?? false;
                                            @endphp
                                            <td class="text-center align-middle" style="{{ $fromRole ? 'background-color: #fffbeb;' : '' }}">
                                                @if($fromRole)
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="Granted by role: {{ $staff->staffRole->name }}">
                                                        <i class="ti ti-shield me-1"></i> Role
                                                    </span>
                                                    <input type="hidden" name="modules[{{ $slug }}][]" value="{{ $action }}">
                                                @else
                                                    <div class="form-check d-flex justify-content-center m-0">
                                                        <input class="form-check-input perm-check"
                                                               type="checkbox"
                                                               name="modules[{{ $slug }}][]"
                                                               value="{{ $action }}"
                                                               data-action="{{ $action }}"
                                                               {{ $fromUser ? 'checked' : '' }}>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 p-3 border-top bg-light align-items-center flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Save Individual Permissions
                        </button>
                        <a href="{{ route('staff.show', $staff->id) }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Cancel
                        </a>
                        @if($staff->staffRole)
                        <a href="{{ route('staff-roles.permissions', $staff->staffRole->id) }}" class="btn btn-outline-warning ms-sm-auto">
                            <i class="ti ti-shield me-1"></i> Edit Role Permissions ({{ $staff->staffRole->name }})
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Column-toggle — only affect actual checkboxes (not role badges)
    document.querySelectorAll('.col-toggle').forEach(function (colChk) {
        colChk.addEventListener('change', function () {
            const col = this.dataset.col;
            document.querySelectorAll(`.perm-check[data-action="${col}"]`)
                .forEach(function (chk) { chk.checked = colChk.checked; });
        });
    });

    // Grant All View (individual checkboxes only)
    document.getElementById('grantAllView').addEventListener('click', function () {
        document.querySelectorAll('.perm-check[data-action="view"]')
            .forEach(function (chk) { chk.checked = true; });
    });

    // Revoke All individual
    document.getElementById('revokeAll').addEventListener('click', function () {
        if (!confirm('Revoke all individually-set permissions? Role permissions will still apply.')) return;
        document.querySelectorAll('.perm-check').forEach(function (chk) { chk.checked = false; });
    });

    // Auto-check view when any write action is checked
    document.querySelectorAll('.perm-check').forEach(function (chk) {
        chk.addEventListener('change', function () {
            if (['create', 'edit', 'delete', 'export', 'import'].includes(this.dataset.action) && this.checked) {
                const row = this.closest('tr');
                const viewChk = row.querySelector('.perm-check[data-action="view"]');
                if (viewChk) viewChk.checked = true;
            }
        });
    });
});
</script>
@endpush
