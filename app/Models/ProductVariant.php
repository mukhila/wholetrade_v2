<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'parent_product_id',
        'variant_sku',
        'size_id',
        'color_id',
        'barcode',
        'price_override',
        'stock',
    ];

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }
}
