<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Package extends Model
{
    protected $fillable = ['type', 'price', 'subscribed'];

    protected $casts = [
        'subscribed' => 'boolean',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(FeatureCategory::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the subscribed attribute, overridden by user's actual subscription status
     */
    public function getSubscribedAttribute($value): bool
    {
        // If no authenticated user, return the default value
        if (!Auth::check()) {
            return $value;
        }

        return $this->subscriptions()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->exists();
    }
}