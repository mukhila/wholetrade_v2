<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceItem extends Model
{
    protected $fillable = [
        'supplier_invoice_id', 'product_id', 'item_description',
        'box_qty', 'item_qty', 'total_qty',
        'unit_price', 'discount_amount', 'tax_rate', 'tax_amount', 'total_price',
    ];

    public function product()          { return $this->belongsTo(Product::class); }
    public function supplierInvoice()  { return $this->belongsTo(SupplierInvoice::class); }
}
