<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureCategory extends Model
{
    protected $fillable = ['package_id', 'name'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }
}