<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class GroupPrice extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'group_id',
        'product_id',
        'unit_price',
        'box_price',
        'price',
        'pos_price',
    ];

    public function group()
    {
        return $this->belongsTo(AssociateGroup::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
