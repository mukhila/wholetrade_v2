<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesCreditNote extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return AuditLog::MODULE_CREDIT_NOTE;
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
        'customer_id',
        'salesperson_id',
        'invoice_id',
        'transaction_number',
        'transaction_date',
        'currency_id',
        'exchange_rate',
        'billing_address',
        'shipping_address',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'tax_amount',
        'round_off',
        'grand_total',
        'remarks',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(SalesCreditNoteItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Associate::class, 'customer_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
