@extends('layouts.app')

@section('title', 'Edit Staff Member — ' . $staff->name)

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Edit Staff Member</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
            <li class="breadcrumb-item active">Edit</li>
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

                <form action="{{ route('staff.update', $staff->id) }}" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $staff->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror"
                                   id="username" name="username" value="{{ old('username', $staff->username) }}" required>
                            <div class="form-text">Staff log in with this username.</div>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email', $staff->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone', $staff->phone) }}" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">New Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" autocomplete="new-password"
                                   placeholder="Leave blank to keep current">
                            <div class="form-text">Leave blank to keep current password (min 8 chars).</div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" class="form-control"
                                   id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                                   placeholder="Re-enter new password">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="staff_role_id" class="form-label fw-semibold">Staff Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('staff_role_id') is-invalid @enderror"
                                    id="staff_role_id" name="staff_role_id">
                                <option value="">Select staff role</option>
                                @foreach($staffRoles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ old('staff_role_id', $staff->staff_role_id) == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('staff_role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">Account Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $staff->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="is_active">Active Account</label>
                            </div>
                            <div class="form-text">Inactive staff cannot log in.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-center pt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('staff.show', $staff->id) }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mt-3 mt-lg-0">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">
                    <i class="ti ti-link text-primary me-1"></i> Quick Links
                </h6>
                <div class="d-grid gap-2">
                    <a href="{{ route('staff.permissions', $staff->id) }}" class="btn btn-soft-warning text-start">
                        <i class="ti ti-shield me-2"></i> Manage Permissions
                    </a>
                    <a href="{{ route('staff.show', $staff->id) }}" class="btn btn-soft-info text-start">
                        <i class="ti ti-eye me-2"></i> View Profile
                    </a>
                    <a href="{{ route('staff.index') }}" class="btn btn-soft-secondary text-start">
                        <i class="ti ti-list me-2"></i> All Staff Members
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
