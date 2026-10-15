<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierCreditNoteItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'supplier_credit_note_id',
        'product_id',
        'gl_account',
        'description',
        'narration',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'total_price',
    ];

    public function creditNote()
    {
        return $this->belongsTo(SupplierCreditNote::class, 'supplier_credit_note_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
