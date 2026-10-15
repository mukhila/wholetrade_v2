<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return AuditLog::MODULE_QUOTATION;
    }

    /**
     * Get the reference code for audit logging
     */
    public function getAuditReferenceCode(): ?string
    {
        return $this->quotation_number;
    }

    protected $fillable = [
        'subscriber_id',
        'quotation_number',
        'salesperson_id',
        'customer_id',
        'status',
        'subtotal',
        'tax_amount',
        'grand_total',
        'remarks',
        'admin_notes',
    ];

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function quotationItems()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function customer()
    {
        return $this->belongsTo(Associate::class, 'customer_id');
    }

    // Accessor for quotation_no to be consistent with usage
    public function getQuotationNoAttribute()
    {
        return $this->quotation_number;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $latest = Quotation::withTrashed()->orderBy('id', 'desc')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            $model->quotation_number = 'QT-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        });
    }
}
