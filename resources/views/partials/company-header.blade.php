<div class="col-8">
    <h4 class="fw-bold fs-3 text-dark mb-1">{{ $companySettings->company_name ?? 'WHOLESALE TRADERS PTE LTD' }}</h4>
    <div class="text-muted small lh-sm">
        <div>{{ $companySettings->address ?? '63 HILLVIEW AVENUE, #10-11 LAM SOON INDUSTRIAL BUILDING, SINGAPORE 669569' }}</div>
        <div>
            Tel: {{ $companySettings->phone_no ?? '+65 84427604' }} 
            @if(isset($companySettings->registration_no) && $companySettings->registration_no) 
                | Comp Reg No: {{ $companySettings->registration_no }} 
            @else 
                | Comp Reg No: 202415901E 
            @endif
        </div>
        <div>
            Email: {{ $companySettings->email ?? 'JMAHENDRAN79@GMAIL.COM' }}
            {{-- Keeping hardcoded Whatsapp for now or fallback as it's not a standard field yet --}}
             | Whatsapp: +65 93411378, +65 87967750
        </div>
    </div>
</div>
