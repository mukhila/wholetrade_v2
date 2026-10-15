<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDebitNoteItem extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'sales_debit_note_id',
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
        return $this->belongsTo(SalesDebitNote::class, 'sales_debit_note_id');
    }
}
