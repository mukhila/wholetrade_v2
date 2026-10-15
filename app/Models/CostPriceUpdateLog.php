<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class CostPriceUpdateLog extends Model
{
    use HasSubscriberScope;

    public $timestamps = false;

    protected $table = 'cost_price_update_logs';

    protected $fillable = [
        'subscriber_id',
        'product_id',
        'old_cost_item',
        'old_cost_box',
        'new_cost_item',
        'new_cost_box',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
        'updated_at'    => 'datetime',
        'old_cost_item' => 'decimal:4',
        'old_cost_box'  => 'decimal:4',
        'new_cost_item' => 'decimal:4',
        'new_cost_box'  => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
