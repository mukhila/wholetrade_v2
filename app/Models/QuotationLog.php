<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationLog extends Model
{
    use HasFactory, HasSubscriberScope;

    public $timestamps = false; // We use changed_at

    protected $fillable = [
        'subscriber_id',
        'quotation_id',
        'status',
        'changed_by',
        'remarks',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}
