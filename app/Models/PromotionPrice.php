<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class PromotionPrice extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'product_id',
        'min_qty',
        'max_qty',
        'unit_price',
        'box_price',
        'pos_price',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
