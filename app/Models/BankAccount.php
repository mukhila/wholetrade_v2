<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'bank_id',
        'account_name',
        'account_number',
        'account_type',
        'opening_balance',
        'current_balance',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'status' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for cash accounts
     */
    public function scopeCash($query)
    {
        return $query->where('account_type', 'cash');
    }

    /**
     * Scope for bank accounts
     */
    public function scopeBankType($query)
    {
        return $query->where('account_type', 'bank');
    }

    /**
     * Get display name with bank
     */
    public function getDisplayNameAttribute()
    {
        if ($this->bank) {
            return $this->bank->name . ' - ' . $this->account_name;
        }
        return $this->account_name;
    }
}
