<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseDeliveryNote extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return AuditLog::MODULE_PURCHASE_DELIVERY;
    }

    /**
     * Get the reference code for audit logging
     */
    public function getAuditReferenceCode(): ?string
    {
        return $this->do_number;
    }

    protected $fillable = [
        'subscriber_id',
        'do_number',
        'delivery_date',
        'supplier_id',
        'receiver_id',
        'currency_id',
        'shipping_address',
        'billing_address',
        'supplier_do_reference',
        'purchase_order_id',
        'remarks',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseDeliveryNoteItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Associate::class, 'supplier_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
