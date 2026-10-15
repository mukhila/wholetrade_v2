<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class PriceAuditLog extends Model
{
    use HasSubscriberScope;

    public $timestamps = false; // Custom timestamp 'changed_at'

    protected $fillable = [
        'subscriber_id',
        'price_type',
        'reference_id',
        'product_id',
        'field_name',
        'old_value',
        'new_value',
        'changed_by',
        'changed_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
