<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;

class ProductLabelAssignment extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'product_id',
        'label_id',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function label()
    {
        return $this->belongsTo(ProductLabel::class, 'label_id');
    }
}
