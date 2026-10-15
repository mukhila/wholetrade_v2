<?php

namespace App\Models;

use App\Traits\HasSubscriberScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssociateProductPrice extends Model
{
    use HasFactory, HasSubscriberScope;

    protected $fillable = [
        'subscriber_id',
        'associate_id',
        'product_id',
        'unit_price',
        'original_price',
        'set_by',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the customer/associate this price belongs to.
     */
    public function associate()
    {
        return $this->belongsTo(Associate::class);
    }

    /**
     * Get the product this price is for.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who set this custom price.
     */
    public function setter()
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * Scope to only active prices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the custom price for a specific customer-product combination.
     * Returns null if no active custom price exists.
     * Uses withoutGlobalScope to ensure prices are found regardless of subscriber context.
     *
     * @param int $associateId
     * @param int $productId
     * @return float|null
     */
    public static function getCustomPrice(int $associateId, int $productId): ?float
    {
        $price = static::withoutGlobalScope('subscriber')
            ->where('associate_id', $associateId)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->value('unit_price');

        return $price !== null ? (float) $price : null;
    }

    /**
     * Set or update custom price for a customer-product combination.
     * Only creates/updates if the price differs from expected price.
     *
     * @param int $associateId
     * @param int $productId
     * @param float $newPrice
     * @param float|null $defaultPrice
     * @param int|null $userId
     * @param string|null $reason
     * @return array ['saved' => bool, 'record' => AssociateProductPrice|null, 'action' => string]
     */
    public static function setCustomPrice(
        int $associateId,
        int $productId,
        float $newPrice,
        ?float $defaultPrice = null,
        ?int $userId = null,
        ?string $reason = null
    ): array {
        // Get existing custom price (bypass subscriber scope to find any existing record)
        $existing = static::withoutGlobalScope('subscriber')
            ->where('associate_id', $associateId)
            ->where('product_id', $productId)
            ->first();

        // Determine the expected price (custom if exists, otherwise default)
        $expectedPrice = $existing?->unit_price ?? $defaultPrice ?? 0;

        // Check if price actually changed (with small tolerance for floating point)
        $priceChanged = abs($newPrice - $expectedPrice) > 0.001;

        if (!$priceChanged) {
            return [
                'saved' => false,
                'record' => $existing,
                'action' => 'no_change'
            ];
        }

        // Get subscriber_id from auth user
        $subscriberId = auth()->user()?->subscriber_id;

        // Price changed - save it
        if ($existing) {
            // Update existing record
            $existing->update([
                'unit_price' => $newPrice,
                'original_price' => $defaultPrice ?? $existing->original_price,
                'set_by' => $userId,
                'reason' => $reason,
                'is_active' => true,
            ]);
            $record = $existing;
            $action = 'updated';
        } else {
            // Create new record with subscriber_id
            $record = static::create([
                'subscriber_id' => $subscriberId,
                'associate_id' => $associateId,
                'product_id' => $productId,
                'unit_price' => $newPrice,
                'original_price' => $defaultPrice,
                'set_by' => $userId,
                'reason' => $reason,
                'is_active' => true,
            ]);
            $action = 'created';
        }

        return [
            'saved' => true,
            'record' => $record,
            'action' => $action,
            'old_price' => $expectedPrice,
            'new_price' => $newPrice,
        ];
    }

    /**
     * Deactivate custom price (soft delete).
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Reactivate custom price.
     */
    public function reactivate(): bool
    {
        return $this->update(['is_active' => true]);
    }
}
