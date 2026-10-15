<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class CompositeProduct extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'composite_product_id',
        'component_product_id',
        'quantity',
    ];

    public function composite()
    {
        return $this->belongsTo(Product::class, 'composite_product_id');
    }

    public function component()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}
