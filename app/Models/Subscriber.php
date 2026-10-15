<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscriber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'plan',
        'status',
        'subscription_start_date',
        'subscription_end_date',
        'is_active',
    ];

    protected $casts = [
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function admin()
    {
        return $this->hasOne(User::class)->where('is_subscriber_admin', true);
    }

    public function isExpired(): bool
    {
        return $this->subscription_end_date->isPast();
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'active' && !$this->isExpired();
    }

    public function daysUntilExpiry(): int
    {
        return now()->diffInDays($this->subscription_end_date, false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('status', 'active')
                     ->where('subscription_end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('subscription_end_date', '<', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('subscription_end_date', '>=', now())
                     ->where('subscription_end_date', '<=', now()->addDays($days));
    }
}
