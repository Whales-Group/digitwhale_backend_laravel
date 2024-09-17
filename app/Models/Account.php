<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "accounts";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "user_id",
        "account_number",
        "account_name",
        "balance",
        "status",
        "type",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        "balance" => "decimal:2",
        "status" => "string",
    ];

    /**
     * Get the user that owns the account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    /**
     * Increment the balance of the account.
     *
     * @param float $amount
     * @return void
     */
    public function incrementBalance(float $amount): void
    {
        $this->increment("balance", $amount);
    }

    /**
     * Decrement the balance of the account.
     *
     * @param float $amount
     * @return void
     */
    public function decrementBalance(float $amount): void
    {
        $this->decrement("balance", $amount);
    }

    /**
     * Check if the account is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === "active";
    }

    /**
     * Check the account type
     *
     * @return bool
     */
    public function accountType(): string
    {
        return $this->type;
    }
}
