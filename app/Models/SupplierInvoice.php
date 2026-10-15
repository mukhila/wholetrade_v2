<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierInvoice extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope, HasAuditLog;

    public function getAuditModule(): string { return 'SupplierInvoice'; }
    public function getAuditReferenceCode(): ?string { return $this->invoice_number; }

    protected $fillable = [
        'subscriber_id', 'invoice_number', 'invoice_date', 'due_date',
        'supplier_id', 'currency_id', 'exchange_rate', 'supplier_ref_number',
        'billing_address', 'remarks',
        'subtotal', 'discount_amount', 'tax_amount', 'net_total',
        'paid_amount', 'balance_due', 'payment_status',
        'status', 'stock_added',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'stock_added'  => 'boolean',
    ];

    public function supplier()  { return $this->belongsTo(Associate::class, 'supplier_id'); }
    public function currency()  { return $this->belongsTo(Currency::class); }
    public function items()     { return $this->hasMany(SupplierInvoiceItem::class); }

    public function canEdit(): bool    { return $this->status === 'draft'; }
    public function canConfirm(): bool { return $this->status === 'draft'; }
    public function canPay(): bool     { return $this->status === 'confirmed' && $this->balance_due > 0; }
}
