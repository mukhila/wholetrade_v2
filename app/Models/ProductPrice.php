<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'product_id',
        'cost_price_item',
        'cost_price_box',
        'margin_type',
        'margin',
        'tax_rate',
        'sales_price_item',
        'sales_price_box',
        'min_sales_price_item',
        'min_sales_price_box',
        'retail_price_item',
        'retail_price_box',
        'min_retail_price_item',
        'min_retail_price_box',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function productprice()
    {
        return $this->belongsTo(Product::class);
    }
}
