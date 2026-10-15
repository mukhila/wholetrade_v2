<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    use HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'action_type',
        'module',
        'reference_type',
        'reference_id',
        'reference_code',
        'old_values',
        'new_values',
        'old_qty',
        'change_qty',
        'new_qty',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'description',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'old_qty' => 'decimal:2',
        'change_qty' => 'decimal:2',
        'new_qty' => 'decimal:2',
    ];

    // Action type constants
    const ACTION_CREATE = 'CREATE';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';
    const ACTION_ADD_STOCK = 'ADD_STOCK';
    const ACTION_DEDUCT_STOCK = 'DEDUCT_STOCK';
    const ACTION_CONFIRM = 'CONFIRM';
    const ACTION_PAYMENT = 'PAYMENT';
    const ACTION_CANCEL = 'CANCEL';
    const ACTION_RESTORE = 'RESTORE';
    const ACTION_PRICE_OVERRIDE = 'PRICE_OVERRIDE';

    // Module constants
    const MODULE_PRODUCT = 'Product';
    const MODULE_SALES_INVOICE = 'SalesInvoice';
    const MODULE_PURCHASE_DELIVERY = 'PurchaseDeliveryNote';
    const MODULE_PURCHASE_ORDER = 'PurchaseOrder';
    const MODULE_SUPPLIER = 'Supplier';
    const MODULE_CUSTOMER = 'Customer';
    const MODULE_QUOTATION = 'Quotation';
    const MODULE_SALES_RECEIPT = 'SalesReceipt';
    const MODULE_CREDIT_NOTE = 'CreditNote';
    const MODULE_DEBIT_NOTE = 'DebitNote';
    const MODULE_CUSTOMER_PRICING = 'CustomerPricing';

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relationship to the referenced model
     */
    public function reference()
    {
        return $this->morphTo('reference');
    }

    /**
     * Create an audit log entry
     *
     * @param string $actionType
     * @param string $module
     * @param Model $model
     * @param array $options
     * @return AuditLog
     */
    public static function log(
        string $actionType,
        string $module,
        Model $model,
        array $options = []
    ): self {
        $user = Auth::user();

        return self::create([
            'subscriber_id' => $user?->subscriber_id,
            'action_type' => $actionType,
            'module' => $module,
            'reference_type' => get_class($model),
            'reference_id' => $model->id,
            'reference_code' => $options['reference_code'] ?? null,
            'old_values' => $options['old_values'] ?? null,
            'new_values' => $options['new_values'] ?? null,
            'old_qty' => $options['old_qty'] ?? null,
            'change_qty' => $options['change_qty'] ?? null,
            'new_qty' => $options['new_qty'] ?? null,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'description' => $options['description'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ]);
    }

    /**
     * Log stock change for a product
     */
    public static function logStockChange(
        Product $product,
        float $changeQty,
        string $actionType,
        string $description,
        array $metadata = []
    ): self {
        $oldQty = $product->getOriginal('stock_qty_pcs') ?? $product->stock_qty_pcs - $changeQty;
        $newQty = $product->stock_qty_pcs;

        return self::log($actionType, self::MODULE_PRODUCT, $product, [
            'reference_code' => $product->sku,
            'old_qty' => $oldQty,
            'change_qty' => $changeQty,
            'new_qty' => $newQty,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope to filter by module
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter by action type
     */
    public function scopeForAction($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope to filter by reference
     */
    public function scopeForReference($query, Model $model)
    {
        return $query->where('reference_type', get_class($model))
                     ->where('reference_id', $model->id);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
