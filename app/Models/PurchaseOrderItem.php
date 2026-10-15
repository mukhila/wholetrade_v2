<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'purchase_order_id',
        'product_id',
        'gl_account',
        'description',
        'narration',
        'box_qty',
        'item_qty',
        'total_qty',
        'received_qty',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_price',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
