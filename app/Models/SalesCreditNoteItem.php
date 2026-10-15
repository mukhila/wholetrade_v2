<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCreditNoteItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'sales_credit_note_id',
        'product_id',
        'description',
        'box_qty',
        'item_qty',
        'box_price',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_price',
    ];

    public function creditNote()
    {
        return $this->belongsTo(SalesCreditNote::class, 'sales_credit_note_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
