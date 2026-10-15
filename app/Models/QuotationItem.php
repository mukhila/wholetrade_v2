<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'quotation_id',
        'product_id',
        'item_qty',
        'box_qty',
        'unit_price',
        'box_price',
        'tax_rate',
        'tax_amount',
        'total_price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
