<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductLabel extends Model
{
    use SoftDeletes, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'label_name',
        'height',
        'width',
        'unit',
        'expiry_required',
        'show_product_name',
        'show_sku',
        'show_barcode',
        'show_batch_no',
        'show_mrp',
        'show_logo',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'expiry_required' => 'boolean',
        'show_product_name' => 'boolean',
        'show_sku' => 'boolean',
        'show_barcode' => 'boolean',
        'show_batch_no' => 'boolean',
        'show_mrp' => 'boolean',
        'show_logo' => 'boolean',
    ];

    public function assignments()
    {
        return $this->hasMany(ProductLabelAssignment::class, 'label_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_label_assignments', 'label_id', 'product_id')
                    ->withPivot('is_default')
                    ->withTimestamps();
    }
}
