@extends('layouts.app')

@section('title', 'Add Subscriber')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h4 class="mb-0">Add New Subscriber</h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('super_admin.subscribers.store') }}" method="POST">
                        @csrf

                        <h5 class="mb-3">Company Information</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name *</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Email *</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Plan *</label>
                                <select name="plan" class="form-select @error('plan') is-invalid @enderror" required>
                                    <option value="basic" {{ old('plan') == 'basic' ? 'selected' : '' }}>Basic</option>
                                    <option value="standard" {{ old('plan') == 'standard' ? 'selected' : '' }}>Standard</option>
                                    <option value="premium" {{ old('plan') == 'premium' ? 'selected' : '' }}>Premium</option>
                                </select>
                                @error('plan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Subscription Start Date *</label>
                                <input type="date" name="subscription_start_date" class="form-control @error('subscription_start_date') is-invalid @enderror" value="{{ old('subscription_start_date', date('Y-m-d')) }}" required>
                                @error('subscription_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subscription End Date *</label>
                                <input type="date" name="subscription_end_date" class="form-control @error('subscription_end_date') is-invalid @enderror" value="{{ old('subscription_end_date', date('Y-m-d', strtotime('+1 year'))) }}" required>
                                @error('subscription_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Admin Account</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Admin Name *</label>
                                <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required>
                                @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Admin Email *</label>
                                <input type="email" name="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" required>
                                @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Admin Password *</label>
                                <input type="password" name="admin_password" class="form-control @error('admin_password') is-invalid @enderror" required>
                                @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('super_admin.subscribers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Subscriber</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
