<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDeliveryNoteItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'purchase_delivery_note_id',
        'product_id',
        'item_description',
        'box_qty',
        'item_qty',
        'total_qty_loose',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'total_price',
    ];

    public function deliveryNote()
    {
        return $this->belongsTo(PurchaseDeliveryNote::class, 'purchase_delivery_note_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
