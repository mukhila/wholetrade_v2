<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSubscriberScope
{
    /**
     * Boot the trait and add global scope for subscriber isolation
     */
    protected static function bootHasSubscriberScope(): void
    {
        // Add global scope to filter by subscriber_id
        static::addGlobalScope('subscriber', function (Builder $query) {
            if (auth()->check()) {
                $user = auth()->user();

                // Super admins can see all data
                if ($user->isSuperAdmin()) {
                    return;
                }

                // Regular users only see their subscriber's data
                if ($user->subscriber_id) {
                    $query->where(function ($q) use ($user) {
                        $q->where('subscriber_id', $user->subscriber_id)
                          ->orWhereNull('subscriber_id');
                    });
                }
            }
        });

        // Auto-assign subscriber_id when creating new records
        static::creating(function ($model) {
            if (auth()->check() && !$model->subscriber_id) {
                $user = auth()->user();

                // Don't auto-assign for super admins unless they have a subscriber_id
                if (!$user->isSuperAdmin() && $user->subscriber_id) {
                    $model->subscriber_id = $user->subscriber_id;
                }
            }
        });
    }

    /**
     * Scope to filter by specific subscriber
     */
    public function scopeForSubscriber(Builder $query, int $subscriberId): Builder
    {
        return $query->withoutGlobalScope('subscriber')
                     ->where('subscriber_id', $subscriberId);
    }

    /**
     * Scope to get all records (bypass subscriber scope) - for super admin use
     */
    public function scopeAllSubscribers(Builder $query): Builder
    {
        return $query->withoutGlobalScope('subscriber');
    }

    /**
     * Relationship to subscriber
     */
    public function subscriber()
    {
        return $this->belongsTo(\App\Models\Subscriber::class);
    }
}
