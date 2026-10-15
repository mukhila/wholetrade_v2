<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierDebitNoteItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'supplier_debit_note_id',
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

    public function debitNote()
    {
        return $this->belongsTo(SupplierDebitNote::class, 'supplier_debit_note_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
