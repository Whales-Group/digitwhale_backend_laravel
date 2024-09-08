<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        "firstName",
        "lastName",
        "email",
        "password",
        "enabled",
        "emailVerifiedAt",
        "tag_id",
    ];

    protected $hidden = ["password", "remember_token"];

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "enabled" => "bool",
        ];
    }

    protected function fullName(): Attribute
    {
        return new Attribute(
            get: fn() => "{$this->firstName} {$this->lastName}"
        );
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}
