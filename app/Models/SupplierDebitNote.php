<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierDebitNote extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return 'SupplierDebitNote';
    }

    /**
     * Get the reference code for audit logging
     */
    public function getAuditReferenceCode(): ?string
    {
        return $this->transaction_number;
    }

    protected $fillable = [
        'subscriber_id',
        'supplier_id',
        'transaction_number',
        'transaction_date',
        'currency_id',
        'exchange_rate',
        'tax_id',
        'billing_address',
        'shipping_address',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'tax_amount',
        'round_off',
        'net_total',
        'remarks',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(SupplierDebitNoteItem::class);
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
