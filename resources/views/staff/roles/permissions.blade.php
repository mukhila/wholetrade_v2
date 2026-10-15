@extends('layouts.app')

@section('title', 'Role Permissions — ' . $staffRole->name)

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Role Permissions &mdash; {{ $staffRole->name }}</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff-roles.index') }}">Roles</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff-roles.show', $staffRole->id) }}">{{ $staffRole->name }}</a></li>
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

<div class="alert alert-info d-flex align-items-start gap-2 mb-3">
    <i class="ti ti-info-circle fs-20 mt-1 flex-shrink-0 text-info"></i>
    <div>
        <strong>Role-level permissions</strong> &mdash; these apply to <strong>all staff members</strong> assigned to the
        <em>{{ $staffRole->name }}</em> role. Individual staff members can be granted <em>additional</em> permissions on their profile page.
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-1 fw-bold">Module Access Matrix</h5>
                        <p class="text-muted mb-0 small">Tick the actions all staff with this role may perform in each module.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="grantViewCreate">
                            <i class="ti ti-checkbox me-1"></i> View + Create
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="grantAllView">
                            <i class="ti ti-eye me-1"></i> Grant All View
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="revokeAll">
                            <i class="ti ti-x me-1"></i> Revoke All
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <form action="{{ route('staff-roles.permissions.update', $staffRole->id) }}" method="POST" id="permissionsForm">
                    @csrf

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0" id="permissionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="min-width:200px;">Module</th>
                                    @foreach(['view' => 'View / Access', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete', 'export' => 'Export', 'import' => 'Import'] as $col => $colLabel)
                                    <th class="text-center" style="width:110px;">
                                        <div class="small fw-bold">{{ $colLabel }}</div>
                                        <div class="form-check d-flex justify-content-center mt-1">
                                            <input class="form-check-input col-toggle" type="checkbox"
                                                   data-col="{{ $col }}" title="Toggle all {{ $colLabel }}">
                                        </div>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $slug => $moduleLabel)
                                    @php $perm = $permissions->get($slug); @endphp
                                    <tr class="permission-row" data-module="{{ $slug }}">
                                        <td class="ps-3 fw-medium align-middle">{{ $moduleLabel }}</td>

                                        @foreach(['view','create','edit','delete','export','import'] as $action)
                                            @php $checked = $perm ? ($perm->{"can_{$action}"} ?? false) : false; @endphp
                                            <td class="text-center align-middle">
                                                <div class="form-check d-flex justify-content-center m-0">
                                                    <input class="form-check-input perm-check"
                                                           type="checkbox"
                                                           name="modules[{{ $slug }}][]"
                                                           value="{{ $action }}"
                                                           data-action="{{ $action }}"
                                                           {{ $checked ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 p-3 border-top bg-light align-items-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Save Role Permissions
                        </button>
                        <a href="{{ route('staff-roles.show', $staffRole->id) }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Cancel
                        </a>
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

    // Column-toggle header checkboxes
    document.querySelectorAll('.col-toggle').forEach(function (colChk) {
        colChk.addEventListener('change', function () {
            const col = this.dataset.col;
            document.querySelectorAll(`.perm-check[data-action="${col}"]`)
                .forEach(function (chk) { chk.checked = colChk.checked; });
        });
    });

    // View + Create Only
    document.getElementById('grantViewCreate').addEventListener('click', function () {
        document.querySelectorAll('.perm-check').forEach(function (chk) { chk.checked = false; });
        document.querySelectorAll('.perm-check[data-action="view"], .perm-check[data-action="create"]')
            .forEach(function (chk) { chk.checked = true; });
    });

    // Grant All View
    document.getElementById('grantAllView').addEventListener('click', function () {
        document.querySelectorAll('.perm-check[data-action="view"]')
            .forEach(function (chk) { chk.checked = true; });
    });

    // Revoke All
    document.getElementById('revokeAll').addEventListener('click', function () {
        if (!confirm('Revoke ALL permissions for this role? Staff members relying only on this role will lose access.')) return;
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
