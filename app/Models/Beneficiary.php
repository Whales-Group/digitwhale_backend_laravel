<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'account_number',
        'bank_name',
        'bank_code',
        'network_provider',
        'phone_number',
        'meter_number',
        'utility_type',
        'plan',
        'amount',
        'description',
        'is_favorite',
        'unique_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_favorite' => 'boolean',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the beneficiary.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter beneficiaries by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter favorite beneficiaries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Get the display name for the beneficiary.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ' (' . $this->type . ')';
    }

    /**
     * Check if the beneficiary is of a specific type.
     *
     * @param string $type
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Mark the beneficiary as favorite.
     *
     * @return void
     */
    public function markAsFavorite(): void
    {
        $this->update(['is_favorite' => true]);
    }

    /**
     * Unmark the beneficiary as favorite.
     *
     * @return void
     */
    public function unmarkAsFavorite(): void
    {
        $this->update(['is_favorite' => false]);
    }
}
// Cash Transfer Beneficiary 
// [
//     'user_id' => 1,
//     'name' => 'John Doe',
//     'type' => 'cash_transfer',
//     'account_number' => '1234567890',
//     'bank_name' => 'GTBank',
//     'is_favorite' => true,
// ]
// Airtime Beneficiary
// [
//     'user_id' => 1,
//     'name' => 'MTN Airtime',
//     'type' => 'airtime',
//     'network_provider' => 'MTN',
//     'phone_number' => '08012345678',
//     'amount' => 1000.00,
//     'is_favorite' => false,
// ]
// Prepaid Meter Beneficiary
// [
//     'user_id' => 1,
//     'name' => 'IKEDC Prepaid',
//     'type' => 'prepaid_meter',
//     'meter_number' => '123456789',
//     'utility_type' => 'electricity',
//     'phone_number' => '08012345678',
//     'amount' => 5000.00,
//     'is_favorite' => true,
// ]
