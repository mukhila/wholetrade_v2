@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Edit Company Settings</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('company-settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
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
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name', $setting->company_name) }}" required>
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="registration_no" class="form-label">Company Reg No</label>
                            <input type="text" class="form-control @error('registration_no') is-invalid @enderror" id="registration_no" name="registration_no" value="{{ old('registration_no', $setting->registration_no) }}">
                            @error('registration_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email ID</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $setting->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $setting->address) }}</textarea>
                         @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="phone_no" class="form-label">Phone No</label>
                            <input type="text" class="form-control @error('phone_no') is-invalid @enderror" id="phone_no" name="phone_no" value="{{ old('phone_no', $setting->phone_no) }}">
                             @error('phone_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="whatsapp_no" class="form-label">WhatsApp No 1</label>
                            <input type="text" class="form-control @error('whatsapp_no') is-invalid @enderror" id="whatsapp_no" name="whatsapp_no" value="{{ old('whatsapp_no', $setting->whatsapp_no) }}" placeholder="+65 12345678">
                             @error('whatsapp_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="whatsapp_no_2" class="form-label">WhatsApp No 2</label>
                            <input type="text" class="form-control @error('whatsapp_no_2') is-invalid @enderror" id="whatsapp_no_2" name="whatsapp_no_2" value="{{ old('whatsapp_no_2', $setting->whatsapp_no_2) }}" placeholder="+65 87654321">
                             @error('whatsapp_no_2')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="person_incharge" class="form-label">Person Incharge</label>
                            <input type="text" class="form-control @error('person_incharge') is-invalid @enderror" id="person_incharge" name="person_incharge" value="{{ old('person_incharge', $setting->person_incharge) }}">
                             @error('person_incharge')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Bank Details Section --}}
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="ti ti-building-bank me-2"></i>Bank Details (For Invoice Footer)</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bank_name" class="form-label">Bank Name</label>
                            <input type="text" class="form-control @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name" value="{{ old('bank_name', $setting->bank_name) }}" placeholder="e.g., OVERSEA-CHINESE BANKING CORPORATION LTD (OCBC)">
                             @error('bank_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bank_account_no" class="form-label">Bank Account No</label>
                            <input type="text" class="form-control @error('bank_account_no') is-invalid @enderror" id="bank_account_no" name="bank_account_no" value="{{ old('bank_account_no', $setting->bank_account_no) }}" placeholder="e.g., 695-305939-001">
                             @error('bank_account_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bank_account_type" class="form-label">Account Type</label>
                            <input type="text" class="form-control @error('bank_account_type') is-invalid @enderror" id="bank_account_type" name="bank_account_type" value="{{ old('bank_account_type', $setting->bank_account_type) }}" placeholder="e.g., BUSINESS GROWTH">
                             @error('bank_account_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bank_branch" class="form-label">Bank Branch</label>
                            <input type="text" class="form-control @error('bank_branch') is-invalid @enderror" id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $setting->bank_branch) }}" placeholder="e.g., Main Branch">
                             @error('bank_branch')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="uen_no" class="form-label">UEN No</label>
                            <input type="text" class="form-control @error('uen_no') is-invalid @enderror" id="uen_no" name="uen_no" value="{{ old('uen_no', $setting->uen_no) }}" placeholder="e.g., 2021123456X">
                             @error('uen_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gst_include" class="form-label">GST Include</label>
                            <select class="form-control @error('gst_include') is-invalid @enderror" id="gst_include" name="gst_include">
                                <option value="No" {{ old('gst_include', $setting->gst_include) == 'No' ? 'selected' : '' }}>No</option>
                                <option value="Yes" {{ old('gst_include', $setting->gst_include) == 'Yes' ? 'selected' : '' }}>Yes</option>
                            </select>
                            @error('gst_include')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Invoice Number Series --}}
                    <hr class="my-4">
                    <h5 class="mb-1"><i class="ti ti-hash me-2"></i>Invoice Number Series</h5>
                    <p class="text-muted small mb-3">Set the prefix for each invoice type. Numbers are auto-generated sequentially per series (e.g., INVG001, INVG002 / INV-001, INV-002).</p>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="gst_invoice_prefix" class="form-label">GST Invoice Prefix</label>
                            <input type="text" class="form-control @error('gst_invoice_prefix') is-invalid @enderror"
                                id="gst_invoice_prefix" name="gst_invoice_prefix"
                                value="{{ old('gst_invoice_prefix', $setting->gst_invoice_prefix ?? 'INVG') }}"
                                placeholder="INVG" maxlength="20">
                            <div class="form-text text-muted">Example: <code>INVG</code> → generates <strong>INVG001</strong>, <strong>INVG002</strong>…</div>
                            @error('gst_invoice_prefix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="non_gst_invoice_prefix" class="form-label">Without GST Invoice Prefix</label>
                            <input type="text" class="form-control @error('non_gst_invoice_prefix') is-invalid @enderror"
                                id="non_gst_invoice_prefix" name="non_gst_invoice_prefix"
                                value="{{ old('non_gst_invoice_prefix', $setting->non_gst_invoice_prefix ?? 'INV-') }}"
                                placeholder="INV-" maxlength="20">
                            <div class="form-text text-muted">Example: <code>INV-</code> → generates <strong>INV-001</strong>, <strong>INV-002</strong>…</div>
                            @error('non_gst_invoice_prefix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-2">
                            <h6 class="text-muted fw-semibold">Thermal Printer (Network / WiFi)</h6>
                            <p class="text-muted small mb-2">Connect printer to WiFi/LAN. Enter IP below for 1-click direct printing with paper cut. No software installation needed.</p>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="thermal_printer_host" class="form-label">Printer IP Address</label>
                            <input type="text" class="form-control @error('thermal_printer_host') is-invalid @enderror"
                                id="thermal_printer_host" name="thermal_printer_host"
                                value="{{ old('thermal_printer_host', $setting->thermal_printer_host) }}"
                                placeholder="e.g., 192.168.1.100">
                            <div class="form-text text-muted">Print a test page on the TSC printer — it prints its own IP address.</div>
                            @error('thermal_printer_host')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="thermal_printer_port" class="form-label">Port</label>
                            <input type="number" class="form-control @error('thermal_printer_port') is-invalid @enderror"
                                id="thermal_printer_port" name="thermal_printer_port"
                                value="{{ old('thermal_printer_port', $setting->thermal_printer_port ?? 9100) }}"
                                placeholder="9100" min="1" max="65535">
                            <div class="form-text text-muted">Default: 9100</div>
                            @error('thermal_printer_port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="thermal_printer_name" class="form-label">Windows Printer Name <span class="text-muted">(optional, Windows only)</span></label>
                            <input type="text" class="form-control @error('thermal_printer_name') is-invalid @enderror" id="thermal_printer_name" name="thermal_printer_name" value="{{ old('thermal_printer_name', $setting->thermal_printer_name) }}" placeholder="e.g., TSC DA200">
                            <div class="form-text text-muted">Only needed if NOT using network/IP mode above.</div>
                            @error('thermal_printer_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="logo" class="form-label">Logo</label>
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/*" onchange="previewImage(this, 'logo-preview')">
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-2">
                                @if($setting->logo)
                                    <p class="small text-muted mb-1">Current: {{ basename($setting->logo) }}</p>
                                    <img id="logo-preview" src="{{ \Storage::disk('public_uploads')->url($setting->logo) }}" alt="Logo Preview" style="max-height: 150px; display: block;">
                                @else
                                    <img id="logo-preview" src="#" alt="Logo Preview" style="max-height: 150px; display: none;">
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="authorized_signature" class="form-label">Authorized Signature</label>
                            <input type="file" class="form-control @error('authorized_signature') is-invalid @enderror" id="authorized_signature" name="authorized_signature" accept="image/*" onchange="previewImage(this, 'signature-preview')">
                            @error('authorized_signature')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-2">
                                @if($setting->authorized_signature)
                                    <p class="small text-muted mb-1">Current: {{ basename($setting->authorized_signature) }}</p>
                                    <img id="signature-preview" src="{{ \Storage::disk('public_uploads')->url($setting->authorized_signature) }}" alt="Signature Preview" style="max-height: 150px; display: block;">
                                @else
                                    <img id="signature-preview" src="#" alt="Signature Preview" style="max-height: 150px; display: none;">
                                @endif
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Settings</button>
                    <a href="{{ route('company-settings.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById(previewId);
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
@endsection
