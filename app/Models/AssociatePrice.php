<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class AssociatePrice extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'associate_id',
        'product_id',
        'unit_price',
        'box_price',
        'price',
        'pos_price',
    ];

    public function associate()
    {
        return $this->belongsTo(Associate::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
