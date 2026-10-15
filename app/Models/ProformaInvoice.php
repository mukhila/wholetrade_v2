<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProformaInvoice extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'pi_number',
        'pi_date',
        'valid_till',
        'customer_id',
        'salesperson_id',
        'currency_id',
        'exchange_rate',
        'billing_address',
        'shipping_address',
        'reference_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'round_off',
        'net_total',
        'remarks',
        'status',
        'is_converted',
        'converted_to_quotation_id',
    ];

    public function items()
    {
        return $this->hasMany(ProformaInvoiceItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Associate::class, 'customer_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
