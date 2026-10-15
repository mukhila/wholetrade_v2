<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'invoice_number',
        'quotation_id',
        'subtotal',
        'tax_amount',
        'grand_total',
        'payment_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->invoice_number = 'INV-' . strtoupper(uniqid());
        });
    }
}
