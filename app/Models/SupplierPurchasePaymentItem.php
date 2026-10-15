<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPurchasePaymentItem extends Model
{
    protected $fillable = [
        'payment_id',
        'purchase_id',
        'amount_paid',
    ];

    public function payment()
    {
        return $this->belongsTo(SupplierPurchasePayment::class, 'payment_id');
    }

    public function purchase()
    {
        return $this->belongsTo(SupplierPurchase::class, 'purchase_id');
    }
}
