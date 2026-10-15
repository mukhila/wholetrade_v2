<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductPrice;

class Product extends Model
{
    use SoftDeletes, HasSubscriberScope, HasAuditLog;

    /**
     * Get the module name for audit logging
     */
    public function getAuditModule(): string
    {
        return AuditLog::MODULE_PRODUCT;
    }

    /**
     * Get the reference code for audit logging
     */
    public function getAuditReferenceCode(): ?string
    {
        return $this->sku;
    }

    protected $fillable = [
        'subscriber_id',
        'sku',
        'name',
        'description',
        'product_type', // STANDARD, VARIANT, COMPOSITE
        'category_id',
        'brand_id',
        'supplier_id',
        'uom_id',
        'barcode',
        'expiry_date',
        'weight',
        'pack_size_id',
        'pack_size_qty',
        'status',
        'inventory_control',
        'min_stock_qty',
        'reorder_qty',
        'stock_qty_pcs',
        'stock_qty_box',
    ];

    protected $casts = [
        'status' => 'boolean',
        'inventory_control' => 'boolean',
        'expiry_date' => 'date',
    ];

    // Relationships

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Associate::class, 'supplier_id')->where('type', 'Supplier');
    }

    public function uom()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function packSize()
    {
        return $this->belongsTo(Size::class, 'pack_size_id');
    }

    public function price()
    {
        return $this->hasOne(ProductPrice::class);
    }

    public function inventory()
    {
        return $this->hasOne(ProductInventory::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'parent_product_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function image()
    {
        return $this->hasOne(ProductImage::class);
    }

    // For Composite Products: The components that make up this product
    public function components()
    {
        return $this->hasMany(CompositeProduct::class, 'composite_product_id');
    }

    public function aliases()
    {
        return $this->hasMany(ProductAlias::class);
    }
}
