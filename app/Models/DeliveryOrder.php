<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'do_number',
        'delivery_date',
        'customer_id',
        'salesperson_id',
        'invoice_id',
        'quotation_id',
        'currency_id',
        'exchange_rate',
        'total_amount',
        'billing_address',
        'shipping_address',
        'remarks',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(DeliveryOrderItem::class);
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

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
