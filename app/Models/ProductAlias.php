<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAlias extends Model
{
    use SoftDeletes, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'product_id',
        'alias_name',
        'alias_sku',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
