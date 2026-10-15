<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPurchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscriber_id',
        'supplier_id',
        'purchase_date',
        'invoice_number',
        'purchase_amount',
        'gst_rate',
        'gst_amount',
        'paid_amount',
        'balance',
        'payment_status',
        'remarks',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Associate::class, 'supplier_id');
    }

    public function paymentItems()
    {
        return $this->hasMany(SupplierPurchasePaymentItem::class, 'purchase_id');
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isDue(): bool
    {
        return in_array($this->payment_status, ['unpaid', 'partial']);
    }
}
