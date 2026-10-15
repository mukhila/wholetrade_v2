<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'sales_invoice_id',
        'product_id',
        'alias_id',
        'gl_account',
        'item_description',
        'box_qty',
        'item_qty',
        'total_qty',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_price',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function alias()
    {
        return $this->belongsTo(ProductAlias::class);
    }
}
