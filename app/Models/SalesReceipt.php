<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReceipt extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return AuditLog::MODULE_SALES_RECEIPT;
    }

    /**
     * Get the reference code for audit logging
     */
    public function getAuditReferenceCode(): ?string
    {
        return $this->receipt_number;
    }

    protected $fillable = [
        'subscriber_id',
        'receipt_number',
        'receipt_date',
        'customer_id',
        'currency_id',
        'exchange_rate',
        'bank_id',
        'bank_account_id',
        'paymode_id',
        'amount',
        'unused_amount',
        'write_off_amount',
        'excess_amount',
        'payment_mode',
        'pay_to',
        'reference',
        'cheque_date',
        'cheque_no',
        'remarks',
        'status',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'cheque_date' => 'date',
        'amount' => 'decimal:2',
        'unused_amount' => 'decimal:2',
        'write_off_amount' => 'decimal:2',
        'excess_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function customer()
    {
        return $this->belongsTo(Associate::class, 'customer_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function paymode()
    {
        return $this->belongsTo(Paymode::class);
    }

    /**
     * Get the currency or fall back to default currency
     */
    public function getCurrencyOrDefault()
    {
        if ($this->currency) {
            return $this->currency;
        }
        return Currency::where('is_default', 1)->first();
    }

    public function invoices()
    {
        return $this->belongsToMany(SalesInvoice::class, 'sales_receipt_allocations')
                    ->withPivot('allocated_amount')
                    ->withTimestamps();
    }
}
