<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class PriceUpdateLog extends Model
{
    use HasSubscriberScope;

    public $timestamps = false;

    protected $fillable = [
        'subscriber_id',
        'product_id',
        'old_unit_price',
        'old_box_price',
        'old_pos_price',
        'new_unit_price',
        'new_box_price',
        'new_pos_price',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
