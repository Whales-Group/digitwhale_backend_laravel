<?php

namespace App\Models;

use App\Models\Account; // Import the Account model
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "first_name",
        "last_name",
        "middle_name",
        "email",
        "tag",
        "password",
        "email_verified_at",
        "dob",
        "profile_url",
        "other_url",
        "phone_number",
        "profile_type"

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        "password",
        "remember_token",
        "email_verified_at",
        "updated_at",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "email_verified_at" => "datetime",
    ];

    /**
     * Get the user's full name.
     *
     * @return Attribute
     */
    public function fullName(): string
    {
        return "{$this->first_name} {$this->middle_name} {$this->last_name}";

    }

    /**
     * Define the relationship with the Account model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function () {

        });
    }

    public function profileIsCompleted(): bool
    {
        $profileFields = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'dob' => $this->dob,
            'profile_url' => $this->profile_url,
            'other_url' => $this->other_url,
            'phone_number' => $this->phone_number,
        ];
    
        return array_reduce($profileFields, fn ($carry, $item) => $carry && !is_null($item), true);
    }
}