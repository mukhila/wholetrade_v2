<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'proforma_invoice_id',
        'product_id',
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

    public function proformaInvoice()
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
