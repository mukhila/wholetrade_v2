<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return AuditLog::MODULE_PURCHASE_ORDER;
    }

    /**
     * Get the reference code for audit logging
     */
    public function getAuditReferenceCode(): ?string
    {
        return $this->po_number;
    }

    protected $fillable = [
        'subscriber_id',
        'po_number',
        'po_date',
        'supplier_id',
        'supplier_ref_no',
        'expected_shipping_date',
        'currency_id',
        'exchange_rate',
        'billing_address',
        'shipping_address',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'net_total',
        'remarks',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Associate::class, 'supplier_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
