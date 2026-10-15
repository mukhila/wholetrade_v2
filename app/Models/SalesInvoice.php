<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return AuditLog::MODULE_SALES_INVOICE;
    }

    /**
     * Get the reference code for audit logging
     */
    public function getAuditReferenceCode(): ?string
    {
        return $this->invoice_number;
    }

    protected $fillable = [
        'subscriber_id',
        'invoice_number',
        'invoice_type',
        'invoice_date',
        'due_date',
        'customer_id',
        'salesperson_id',
        'currency_id',
        'exchange_rate',
        'billing_address',
        'shipping_address',
        'reference_number',
        'quotation_id',
        'delivery_order_id',
        'gross_total',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'round_off',
        'net_total',
        'paid_amount',
        'balance_due',
        'payment_status',
        'remarks',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Associate::class, 'customer_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get all receipts/payments allocated to this invoice.
     */
    public function receipts()
    {
        return $this->belongsToMany(SalesReceipt::class, 'sales_receipt_allocations')
            ->withPivot('allocated_amount')
            ->withTimestamps();
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Editable any time except when fully paid or voided.
     */
    public function canEdit(): bool
    {
        return !$this->isVoided() && !$this->isPaid();
    }

    /**
     * Can be voided as long as it has not been fully paid.
     */
    public function canVoid(): bool
    {
        return !$this->isVoided() && !$this->isPaid();
    }

    /**
     * Receives payment when active/posted and balance remains.
     */
    public function canReceivePayment(): bool
    {
        return !$this->isVoided() && $this->balance_due > 0;
    }
}
