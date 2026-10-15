<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReference extends Model
{
    protected $fillable = [
        'invoice_no',
        'date',
        'customer_id',
        'customer_name',
        'net_total',
        'paid',
        'balance',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'net_total' => 'decimal:2',
        'paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Associate::class, 'customer_id');
    }
}
