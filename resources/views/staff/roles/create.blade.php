@extends('layouts.app')

@section('title', 'Add Staff Role')

@section('content')
<div class="page-title-head d-flex align-items-center justify-content-between mb-3">
    <div class="flex-grow-1">
        <h4 class="fs-xl fw-bold m-0">Add Staff Role</h4>
    </div>
    <div class="text-end">
        <ol class="breadcrumb m-0 py-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
            <li class="breadcrumb-item"><a href="{{ route('staff-roles.index') }}">Roles</a></li>
            <li class="breadcrumb-item active">Add</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
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

                <form action="{{ route('staff-roles.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}"
                               placeholder="e.g. Sales Executive, Cashier, Warehouse Staff"
                               required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3"
                                  placeholder="Optional description of responsibilities...">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2 align-items-center pt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Create Role
                        </button>
                        <a href="{{ route('staff-roles.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mt-3 mt-lg-0">
        <div class="card bg-light border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">
                    <i class="ti ti-info-circle text-primary me-1"></i> About Staff Roles
                </h6>
                <p class="text-muted small mb-2">Staff roles are job positions (e.g. <strong>Sales Executive</strong>, <strong>Cashier</strong>, <strong>Warehouse Supervisor</strong>).</p>
                <p class="text-muted small mb-0">After creating a role, you can set default permissions for that role which will apply automatically to all staff members assigned to it.</p>
            </div>
        </div>
    </div>
</div>
@endsection
