<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Add stock to a product (from supplier/purchase)
     *
     * @param Product $product
     * @param int $boxQty
     * @param int $pcsQty
     * @param string $source Description of where stock came from
     * @param array $metadata Additional context (PO number, supplier, etc.)
     * @return array Returns old and new quantities
     */
    public static function addStock(
        Product $product,
        int $boxQty,
        int $pcsQty,
        string $source,
        array $metadata = []
    ): array {
        $packSize = max(1, (int)($product->pack_size_qty ?? 1));
        $totalPcsToAdd = ($boxQty * $packSize) + $pcsQty;

        // Store old values
        $oldPcs = (int)($product->stock_qty_pcs ?? 0);
        $oldBox = (int)($product->stock_qty_box ?? 0);

        // Add directly: boxes to boxes, pcs to pcs.
        // Do NOT redistribute — received pcs stay as pcs, received boxes stay as boxes.
        $newBox = $oldBox + $boxQty;
        $newPcs = $oldPcs + $pcsQty;

        // Update product stock
        $product->update([
            'stock_qty_pcs' => $newPcs,
            'stock_qty_box' => $newBox,
        ]);

        // Also update product_inventories if exists
        $inventory = ProductInventory::firstOrCreate(
            ['product_id' => $product->id],
            ['stock_in_hand' => 0]
        );
        $inventory->increment('stock_in_hand', $totalPcsToAdd);

        // Create audit log
        AuditLog::log(
            AuditLog::ACTION_ADD_STOCK,
            AuditLog::MODULE_PRODUCT,
            $product,
            [
                'reference_code' => $product->sku,
                'old_qty' => $oldPcs,
                'change_qty' => $totalPcsToAdd,
                'new_qty' => $newPcs,
                'old_values' => [
                    'stock_qty_pcs' => $oldPcs,
                    'stock_qty_box' => $oldBox,
                ],
                'new_values' => [
                    'stock_qty_pcs' => $newPcs,
                    'stock_qty_box' => $newBox,
                ],
                'description' => "Stock added: +{$totalPcsToAdd} pcs ({$boxQty} box + {$pcsQty} pcs) from {$source}",
                'metadata' => array_merge($metadata, [
                    'box_qty_added' => $boxQty,
                    'pcs_qty_added' => $pcsQty,
                    'pack_size' => $packSize,
                    'source' => $source,
                ]),
            ]
        );

        return [
            'old_pcs' => $oldPcs,
            'old_box' => $oldBox,
            'added_pcs' => $totalPcsToAdd,
            'new_pcs' => $newPcs,
            'new_box' => $newBox,
        ];
    }

    /**
     * Deduct stock from a product (from sales)
     *
     * @param Product $product
     * @param int $boxQty  Number of boxes to deduct
     * @param int $pcsQty  Number of loose pieces to deduct
     * @param string $source Description of why stock was deducted
     * @param array $metadata Additional context (Invoice number, customer, etc.)
     * @return array Returns old and new quantities
     */
    public static function deductStock(
        Product $product,
        int $boxQty,
        int $pcsQty,
        string $source,
        array $metadata = []
    ): array {
        $packSize = max(1, (int)($product->pack_size_qty ?? 1));

        $oldPcs = (int)($product->stock_qty_pcs ?? 0);
        $oldBox = (int)($product->stock_qty_box ?? 0);

        // Deduct directly — boxes from box stock, pcs from pcs stock.
        $newBox = $oldBox - $boxQty;
        $newPcs = $oldPcs - $pcsQty;

        // If pcs goes negative, borrow whole boxes to cover the shortfall.
        if ($newPcs < 0 && $newBox > 0) {
            $borrow  = (int)ceil(abs($newPcs) / $packSize);
            $borrow  = min($borrow, $newBox);
            $newBox -= $borrow;
            $newPcs += $borrow * $packSize;
        }

        $totalPcsDeducted = ($boxQty * $packSize) + $pcsQty;

        $product->update([
            'stock_qty_pcs' => $newPcs,
            'stock_qty_box' => $newBox,
        ]);

        $inventory = ProductInventory::where('product_id', $product->id)->first();
        if ($inventory) {
            $inventory->decrement('stock_in_hand', $totalPcsDeducted);
        }

        AuditLog::log(
            AuditLog::ACTION_DEDUCT_STOCK,
            AuditLog::MODULE_PRODUCT,
            $product,
            [
                'reference_code' => $product->sku,
                'old_qty' => $oldPcs,
                'change_qty' => -$totalPcsDeducted,
                'new_qty' => $newPcs,
                'old_values' => [
                    'stock_qty_pcs' => $oldPcs,
                    'stock_qty_box' => $oldBox,
                ],
                'new_values' => [
                    'stock_qty_pcs' => $newPcs,
                    'stock_qty_box' => $newBox,
                ],
                'description' => "Stock deducted: -{$totalPcsDeducted} pcs ({$boxQty} box + {$pcsQty} pcs) for {$source}",
                'metadata' => array_merge($metadata, [
                    'box_qty_deducted' => $boxQty,
                    'pcs_qty_deducted' => $pcsQty,
                    'pack_size' => $packSize,
                    'source' => $source,
                ]),
            ]
        );

        return [
            'old_pcs'       => $oldPcs,
            'old_box'       => $oldBox,
            'deducted_pcs'  => $totalPcsDeducted,
            'new_pcs'       => $newPcs,
            'new_box'       => $newBox,
        ];
    }

    /**
     * Adjust stock (can be positive or negative)
     *
     * @param Product $product
     * @param int $adjustmentQty Positive to add, negative to deduct
     * @param string $reason Reason for adjustment
     * @param array $metadata Additional context
     * @return array
     */
    public static function adjustStock(
        Product $product,
        int $adjustmentQty,
        string $reason,
        array $metadata = []
    ): array {
        if ($adjustmentQty >= 0) {
            return self::addStock($product, 0, $adjustmentQty, "Adjustment: {$reason}", $metadata);
        } else {
            return self::deductStock($product, 0, abs($adjustmentQty), "Adjustment: {$reason}", $metadata);
        }
    }

    /**
     * Get stock history for a product
     *
     * @param Product $product
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getStockHistory(Product $product, int $limit = 50)
    {
        return AuditLog::where('reference_type', Product::class)
            ->where('reference_id', $product->id)
            ->whereIn('action_type', [AuditLog::ACTION_ADD_STOCK, AuditLog::ACTION_DEDUCT_STOCK])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
