<?php

namespace App\Traits;

use App\Models\AuditLog;

trait HasAuditLog
{
    /**
     * Boot the trait
     */
    public static function bootHasAuditLog(): void
    {
        // Log when a model is created
        static::created(function ($model) {
            if ($model->shouldAuditLog()) {
                AuditLog::log(
                    AuditLog::ACTION_CREATE,
                    $model->getAuditModule(),
                    $model,
                    [
                        'reference_code' => $model->getAuditReferenceCode(),
                        'new_values' => $model->getAuditableAttributes(),
                        'description' => $model->getAuditModule() . ' created: ' . $model->getAuditReferenceCode(),
                    ]
                );
            }
        });

        // Log when a model is updated
        static::updated(function ($model) {
            if ($model->shouldAuditLog() && $model->wasChanged()) {
                $oldValues = [];
                $newValues = [];

                foreach ($model->getChanges() as $key => $value) {
                    if (!in_array($key, ['updated_at'])) {
                        $oldValues[$key] = $model->getOriginal($key);
                        $newValues[$key] = $value;
                    }
                }

                if (!empty($newValues)) {
                    AuditLog::log(
                        AuditLog::ACTION_UPDATE,
                        $model->getAuditModule(),
                        $model,
                        [
                            'reference_code' => $model->getAuditReferenceCode(),
                            'old_values' => $oldValues,
                            'new_values' => $newValues,
                            'description' => $model->getAuditModule() . ' updated: ' . $model->getAuditReferenceCode(),
                        ]
                    );
                }
            }
        });

        // Log when a model is deleted
        static::deleted(function ($model) {
            if ($model->shouldAuditLog()) {
                AuditLog::log(
                    AuditLog::ACTION_DELETE,
                    $model->getAuditModule(),
                    $model,
                    [
                        'reference_code' => $model->getAuditReferenceCode(),
                        'old_values' => $model->getAuditableAttributes(),
                        'description' => $model->getAuditModule() . ' deleted: ' . $model->getAuditReferenceCode(),
                    ]
                );
            }
        });
    }

    /**
     * Check if this model should be audit logged
     * Override in model to customize
     */
    public function shouldAuditLog(): bool
    {
        return true;
    }

    /**
     * Get the module name for audit logging
     * Override in model to customize
     */
    public function getAuditModule(): string
    {
        return class_basename($this);
    }

    /**
     * Get the reference code for audit logging (e.g., SKU, Invoice Number)
     * Override in model to customize
     */
    public function getAuditReferenceCode(): ?string
    {
        // Try common reference fields
        return $this->sku
            ?? $this->invoice_number
            ?? $this->do_number
            ?? $this->receipt_number
            ?? $this->code
            ?? $this->name
            ?? (string) $this->id;
    }

    /**
     * Get attributes to include in audit log
     * Override in model to customize
     */
    public function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();

        // Remove sensitive or unnecessary fields
        $exclude = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];

        return array_diff_key($attributes, array_flip($exclude));
    }

    /**
     * Get all audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'reference');
    }

    /**
     * Create a custom audit log entry
     */
    public function logAudit(string $actionType, string $description, array $options = []): AuditLog
    {
        return AuditLog::log($actionType, $this->getAuditModule(), $this, array_merge([
            'reference_code' => $this->getAuditReferenceCode(),
            'description' => $description,
        ], $options));
    }
}
