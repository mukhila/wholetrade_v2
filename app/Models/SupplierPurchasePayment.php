<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPurchasePayment extends Model
{
    protected $fillable = [
        'subscriber_id',
        'supplier_id',
        'payment_number',
        'payment_date',
        'total_amount',
        'payment_mode',
        'reference_number',
        'giro_date',
        'giro_is_paid',
        'remarks',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'giro_date'    => 'date',
        'giro_is_paid' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Associate::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(SupplierPurchasePaymentItem::class, 'payment_id');
    }
}
