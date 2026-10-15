@extends('layouts.app')

@section('title', 'Add Staff Member')

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Add Staff Member</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
            <li class="breadcrumb-item active">Add</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('staff.store') }}" method="POST" autocomplete="off" id="createStaffForm">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" placeholder="e.g. John Doe" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror"
                                   id="username" name="username" value="{{ old('username') }}" required autocomplete="off"
                                   placeholder="e.g. john_doe">
                            <div class="form-text">Staff will use this to log in (letters, numbers, _ . -)</div>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}" autocomplete="off"
                                   placeholder="john@example.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone') }}" required
                                   placeholder="+60123456789">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" required minlength="8" autocomplete="new-password"
                                   placeholder="Minimum 8 characters">
                            <div class="form-text">Minimum 8 characters.</div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control"
                                   id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                                   placeholder="Re-enter password">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="staff_role_id" class="form-label fw-semibold">Staff Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('staff_role_id') is-invalid @enderror"
                                id="staff_role_id" name="staff_role_id" required>
                            <option value="">Select staff role</option>
                            @foreach($staffRoles as $role)
                                <option value="{{ $role->id }}" {{ old('staff_role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('staff_role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($staffRoles->isEmpty())
                            <div class="form-text text-warning">
                                <i class="ti ti-alert-triangle me-1"></i> No roles defined yet. Please <a href="{{ route('staff-roles.create') }}" target="_blank">create a role first</a> before adding staff.
                            </div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 align-items-center pt-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="ti ti-user-plus me-1"></i> Create & Set Permissions
                        </button>
                        <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mt-3 mt-lg-0">
        <div class="card bg-light border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">
                    <i class="ti ti-info-circle text-primary me-1"></i> What happens next?
                </h6>
                <p class="text-muted small mb-2">
                    After creating the staff member, you will be taken directly to the <strong>permissions page</strong> where you can view role permissions and assign individual module overrides.
                </p>
                <hr class="my-3">
                <p class="text-muted small mb-0">
                    Staff members log in with their <strong>username</strong> and password. Inactive staff accounts cannot access the portal.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('createStaffForm').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
});
</script>
@endpush
@endsection
