@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
                        <li class="breadcrumb-item active">My Profile</li>
                    </ol>
                </div>
                <h4 class="page-title">My Profile</h4>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Personal Information -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Personal Information</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}">
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone No</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role_display" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role_display"
                                    value="@if($user->is_super_admin)Super Admin@elseif($user->staffRole){{ $user->staffRole->name }}@elseif($user->roles->isNotEmpty()){{ $user->roles->pluck('name')->join(', ') }}@else N/A @endif"
                                    disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status_display" class="form-label">Status</label>
                                <input type="text" class="form-control" id="status_display"
                                    value="{{ $user->is_active ? 'Active' : 'Inactive' }}"
                                    disabled>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Security</h4>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="togglePasswordSection">
                        <label class="form-check-label" for="togglePasswordSection">Change Password</label>
                    </div>
                </div>
                <div class="card-body">
                    <div id="passwordSection" style="display: none;">
                        <form action="{{ route('profile.password.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password">
                                    <div class="input-group-text" data-password="false">
                                        <span class="password-eye"></span>
                                    </div>
                                    @error('current_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" class="form-control @error('new_password') is-invalid @enderror" id="new_password" name="new_password">
                                    <div class="input-group-text" data-password="false">
                                        <span class="password-eye"></span>
                                    </div>
                                    @error('new_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation">
                                    <div class="input-group-text" data-password="false">
                                        <span class="password-eye"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-danger">
                                    <i class="ti ti-lock me-1"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                    <div id="passwordPlaceholder" class="text-center py-4 text-muted">
                        <i class="ti ti-lock-access fs-24 mb-2 d-block"></i>
                        Toggle the switch above to change your password.
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggle = document.getElementById('togglePasswordSection');
        const section = document.getElementById('passwordSection');
        const placeholder = document.getElementById('passwordPlaceholder');

        // Toggle Visibility Logic
        toggle.addEventListener('change', function() {
            if (this.checked) {
                section.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                section.style.display = 'none';
                placeholder.style.display = 'block';
            }
        });

        // If there are errors, ensure section is open
        @if($errors->has('current_password') || $errors->has('new_password'))
            toggle.checked = true;
            toggle.dispatchEvent(new Event('change'));
        @endif
    });
</script>
@endpush
