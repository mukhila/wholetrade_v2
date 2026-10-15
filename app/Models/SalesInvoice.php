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

    /**
     * Check if the invoice is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the invoice is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if the invoice can be edited (only drafts).
     */
    public function canEdit(): bool
    {
        return $this->isDraft();
    }

    /**
     * Check if the invoice can be confirmed (only drafts).
     */
    public function canConfirm(): bool
    {
        return $this->isDraft();
    }

    /**
     * Check if the invoice can receive payments (confirmed or posted with balance due).
     * Supports 'posted' for backward compatibility with existing data.
     */
    public function canReceivePayment(): bool
    {
        return ($this->isConfirmed() || $this->status === 'posted') && $this->balance_due > 0;
    }
}
