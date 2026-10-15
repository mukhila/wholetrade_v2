@extends('layouts.app')

@push('styles')
<style>
.form-check.form-switch .form-check-input {
    display: inline-block !important;
    opacity: 1 !important;
    visibility: visible !important;
}
</style>
@endpush

@section('content')

		<div class="page-title-head d-flex align-items-center ">
			<div class="flex-grow-1">
				<h4 class="fs-xl fw-bold m-0">Create Associate</h4>
			</div>

			<div class="text-end">
				<ol class="breadcrumb m-0 py-0">
					<li class="breadcrumb-item"><a href="javascript: void(0);">Whole Trade</a></li>
					<li class="breadcrumb-item"><a href="{{ route('associates.index') }}">Associates</a></li>
					<li class="breadcrumb-item active">Create</li>
				</ol>
			</div>
		</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('associates.store') }}" method="POST">
                    @csrf
                    
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <!-- Basic Info -->
                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Associate Code</label>
                            <input type="text" class="form-control" id="code" name="code" value="{{ $code }}" readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="parent_associate_id" class="form-label">Parent Associate</label>
                            <select class="form-select" id="parent_associate_id" name="parent_associate_id">
                                <option value="">Select Parent (Optional)</option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_associate_id') == $parent->id ? 'selected' : '' }}>{{ $parent->name }} ({{ $parent->code }})</option>
                                @endforeach
                            </select>
                        </div>

                         <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Associate Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="Customer" {{ old('type') == 'Customer' ? 'selected' : '' }}>Customer</option>
                                <option value="Supplier" {{ old('type') == 'Supplier' ? 'selected' : '' }}>Supplier</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>

                         <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                        </div>

                        <!-- Contact Info -->
                         <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="alternate_phone" class="form-label">Alternate Phone</label>
                            <input type="text" class="form-control" id="alternate_phone" name="alternate_phone" value="{{ old('alternate_phone') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" value="{{ old('contact_person') }}" placeholder="Person to contact">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address_line_1" name="address_line_1" value="{{ old('address_line_1') }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="address_line_2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="address_line_2" name="address_line_2" value="{{ old('address_line_2') }}">
                        </div>
                        
                         <div class="col-md-6 mb-3">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code') }}">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" value="{{ old('website') }}">
                        </div>

                        <!-- Business -->
                        <div class="col-md-6 mb-3">
                            <label for="credit_limit" class="form-label">Credit Limit</label>
                            <input type="number" step="0.01" class="form-control" id="credit_limit" name="credit_limit" value="{{ old('credit_limit') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tax_id" class="form-label">Tax</label>
                            <select class="form-select" id="tax_id" name="tax_id">
                                <option value="">Select Tax</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}" {{ old('tax_id') == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ $tax->percentage }}%)</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3" id="gst_option_group">
                            <label for="gst_option" class="form-label">GST Option <span class="text-danger">*</span></label>
                            <select class="form-select" id="gst_option" name="gst_option">
                                <option value="E" {{ old('gst_option', 'E') == 'E' ? 'selected' : '' }}>GST Exclusive</option>
                                <option value="I" {{ old('gst_option') == 'I' ? 'selected' : '' }}>GST Inclusive</option>
                                <option value="N" {{ old('gst_option') == 'N' ? 'selected' : '' }}>No GST</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="currency_id" class="form-label">Currency</label>
                            <select class="form-select" id="currency_id" name="currency_id">
                                <option value="">Select Currency</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" {{ (old('currency_id') == $currency->id) || (isset($defaultCurrency) && $defaultCurrency->id == $currency->id) ? 'selected' : '' }}>
                                        {{ $currency->name }} ({{ $currency->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                         <div class="col-md-6 mb-3">
                            <label for="associate_group_id" class="form-label">Associate Group</label>
                            <select class="form-select" id="associate_group_id" name="associate_group_id">
                                <option value="">Select Group</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ old('associate_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="salesman_id" class="form-label">Salesman</label>
                            <select class="form-select" id="salesman_id" name="salesman_id">
                                <option value="">Select Salesman</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('salesman_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" value="1" checked>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">Create Associate</button>
                    <a href="{{ route('associates.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var nameInput = document.getElementById('name');
    var usernameInput = document.getElementById('username');
    var isManuallyEdited = false;

    if (usernameInput.value) {
        isManuallyEdited = true;
    }

    usernameInput.addEventListener('input', function () {
        isManuallyEdited = true;
    });

    nameInput.addEventListener('input', function () {
        if (isManuallyEdited) return;
        
        var nameVal = nameInput.value;
        var slug = nameVal.toLowerCase()
            .replace(/[^a-z0-9\-_]/g, '-') // Replace non-alphanumeric with dash
            .replace(/-+/g, '-')          // Replace multiple dashes with single dash
            .replace(/^-+|-+$/g, '');     // Trim dashes from start and end
        
        usernameInput.value = slug.substring(0, 50);
    });
});
</script>
@endpush
