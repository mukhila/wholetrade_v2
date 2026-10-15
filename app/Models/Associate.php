<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Associate extends Model
{
    use SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return $this->type === 'Supplier' ? AuditLog::MODULE_SUPPLIER : AuditLog::MODULE_CUSTOMER;
    }

    /**
     * Get the reference code for audit logging
     */
    public function getAuditReferenceCode(): ?string
    {
        return $this->code ?? $this->name;
    }

    protected $fillable = [
        'subscriber_id',
        'type',
        'code',
        'username', // Added username
        'parent_associate_id',
        'name',
        'status',
        'address_line_1',
        'address_line_2',
        'phone',
        'contact_person',
        'alternate_phone',
        'postal_code',
        'email',
        'website',
        'credit_limit',
        'tax_id',
        'gst_option',
        'currency_id',
        'associate_group_id',
        'salesman_id',
        'password',
    ];

    protected $casts = [
        'status' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    public function parent()
    {
        return $this->belongsTo(Associate::class, 'parent_associate_id');
    }

    public function children()
    {
        return $this->hasMany(Associate::class, 'parent_associate_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function associateGroup()
    {
        return $this->belongsTo(AssociateGroup::class);
    }

    public function salesman()
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function invoiceReferences()
    {
        return $this->hasMany(InvoiceReference::class, 'customer_id');
    }

    /**
     * Get all sales invoices for this customer
     */
    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class, 'customer_id');
    }

    /**
     * Calculate total outstanding balance for this customer.
     * Includes both old (invoice_references) and new (sales_invoices) systems.
     *
     * @return float
     */
    public function getTotalOutstandingBalance(): float
    {
        // Old invoices (invoice_references table)
        $oldBalance = $this->invoiceReferences()
            ->where('balance', '>', 0)
            ->sum('balance');

        // New invoices (sales_invoices table)
        $newBalance = $this->salesInvoices()
            ->whereIn('status', ['confirmed', 'posted'])
            ->where('balance_due', '>', 0)
            ->sum('balance_due');

        return (float) $oldBalance + (float) $newBalance;
    }

    /**
     * Get outstanding balance attribute (accessor)
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->getTotalOutstandingBalance();
    }
}
