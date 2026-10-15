@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Company Settings</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-12 text-end">
                        @if($canEdit)
                        <a href="{{ route('company-settings.edit') }}" class="btn btn-primary">Edit Settings</a>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Company Name:</strong> {{ $setting->company_name }}</p>
                        <p><strong>Company Reg No:</strong> {{ $setting->registration_no }}</p>
                        <p><strong>Email ID:</strong> {{ $setting->email }}</p>
                        <p><strong>Phone No:</strong> {{ $setting->phone_no }}</p>
                        <p><strong>WhatsApp No 1:</strong> {{ $setting->whatsapp_no }}</p>
                        <p><strong>WhatsApp No 2:</strong> {{ $setting->whatsapp_no_2 }}</p>
                        <p><strong>Person Incharge:</strong> {{ $setting->person_incharge }}</p>
                        <p><strong>Address:</strong> {{ $setting->address }}</p>

                        <h5 class="mt-4">Bank Details</h5>
                        <p><strong>Bank Name:</strong> {{ $setting->bank_name }}</p>
                        <p><strong>Bank Account No:</strong> {{ $setting->bank_account_no }}</p>
                        <p><strong>Account Type:</strong> {{ $setting->bank_account_type }}</p>
                        <p><strong>Bank Branch:</strong> {{ $setting->bank_branch }}</p>
                        <p><strong>UEN No:</strong> {{ $setting->uen_no }}</p>
                        <p><strong>GST Include:</strong> {{ $setting->gst_include ?? 'No' }}</p>

                        <h5 class="mt-4">Invoice Number Series</h5>
                        <p><strong>GST Invoice Prefix:</strong> <code>{{ $setting->gst_invoice_prefix ?? 'INV-G' }}</code> &nbsp;&rarr;&nbsp; e.g. <strong>{{ ($setting->gst_invoice_prefix ?? 'INV-G') . '0001' }}</strong></p>
                        <p><strong>Without GST Invoice Prefix:</strong> <code>{{ $setting->non_gst_invoice_prefix ?? 'INV-' }}</code> &nbsp;&rarr;&nbsp; e.g. <strong>{{ ($setting->non_gst_invoice_prefix ?? 'INV-') . '0001' }}</strong></p>
                    </div>
                   @if($setting->logo)
                   <img src="{{ \Storage::disk('public_uploads')->url($setting->logo) }}" alt="Logo" style="height: 60px; max-width: 150px; object-fit: contain;">
                   @endif

                   @if($setting->authorized_signature)
                   <img src="{{ \Storage::disk('public_uploads')->url($setting->authorized_signature) }}"
                        alt="Authorized Signature"
                        style="height: 60px; max-width: 150px; object-fit: contain;">
                   @endif

                </div>
            </div>
        </div>
    </div
</div>
@endsection
