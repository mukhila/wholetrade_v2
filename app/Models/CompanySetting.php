<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'company_name',
        'registration_no',
        'logo',
        'address',
        'phone_no',
        'whatsapp_no',
        'whatsapp_no_2',
        'person_incharge',
        'email',
        'authorized_signature',
        'bank_name',
        'bank_account_no',
        'bank_account_type',
        'uen_no',
        'bank_branch',
        'gst_include',
        'gst_invoice_prefix',
        'non_gst_invoice_prefix',
        'thermal_printer_name',
        'thermal_printer_host',
        'thermal_printer_port',
    ];
}
