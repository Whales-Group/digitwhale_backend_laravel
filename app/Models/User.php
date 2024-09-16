<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "password",
        "email_verified_at",
    ];

    protected $hidden = [
        "password",
        "remember_token",
        "email_verified_at",
        "updated_at",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
    ];

    public function fullName(): Attribute
    {
        return new Attribute(
            get: fn() => "{$this->first_name} {$this->last_name}"
        );
    }

    public function tag()
    {
        return $this->hasOne(Tag::class);
    }

    public function account()
    {
        return $this->hasOne(Account::class);
    }

    protected static function booted()
    {
        static::created(function ($user) {
            $tag = Tag::create([
                "tag" => request()->tag,
                "user_id" => $user->id,
            ]);
        });
    }
}
