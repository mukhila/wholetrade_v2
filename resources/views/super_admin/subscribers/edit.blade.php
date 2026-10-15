@extends('layouts.app')

@section('title', 'Edit Subscriber')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h4 class="mb-0">Edit Subscriber: {{ $subscriber->name }}</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('super_admin.subscribers.update', $subscriber) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name *</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $subscriber->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Email *</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $subscriber->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $subscriber->phone) }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Plan *</label>
                                <select name="plan" class="form-select @error('plan') is-invalid @enderror" required>
                                    <option value="basic" {{ old('plan', $subscriber->plan) == 'basic' ? 'selected' : '' }}>Basic</option>
                                    <option value="standard" {{ old('plan', $subscriber->plan) == 'standard' ? 'selected' : '' }}>Standard</option>
                                    <option value="premium" {{ old('plan', $subscriber->plan) == 'premium' ? 'selected' : '' }}>Premium</option>
                                </select>
                                @error('plan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $subscriber->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status', $subscriber->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="cancelled" {{ old('status', $subscriber->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $subscriber->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Subscription Start Date *</label>
                                <input type="date" name="subscription_start_date" class="form-control @error('subscription_start_date') is-invalid @enderror" value="{{ old('subscription_start_date', $subscriber->subscription_start_date->format('Y-m-d')) }}" required>
                                @error('subscription_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subscription End Date *</label>
                                <input type="date" name="subscription_end_date" class="form-control @error('subscription_end_date') is-invalid @enderror" value="{{ old('subscription_end_date', $subscriber->subscription_end_date->format('Y-m-d')) }}" required>
                                @error('subscription_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('super_admin.subscribers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Subscriber</button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h5 class="mb-3">Quick Extend</h5>
                    <div class="row mb-4">
                        <div class="col-auto">
                            <form action="{{ route('super_admin.subscribers.extend', $subscriber) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input type="number" name="days" class="form-control" value="30" min="1" style="width: 100px;">
                                <button type="submit" class="btn btn-outline-success">Extend Days</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
