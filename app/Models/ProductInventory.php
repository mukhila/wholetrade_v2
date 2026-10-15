<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class ProductInventory extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'product_id',
        'stock_in_hand',
        'stock_value',
        'have_batch',
        'have_serial',
        'lot_no',
    ];

    protected $casts = [
        'have_batch' => 'boolean',
        'have_serial' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
