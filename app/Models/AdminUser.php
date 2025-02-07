<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Account; // Import the Account model

class AdminUser extends Authenticatable
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
}

// Assigning Roles and Permissions
// When creating or updating an admin user, you can assign roles and permissions as follows:

// Create a new admin user
// $adminUser = \App\Models\AdminUser::create([
//     "email" => "admin@example.com",
//     "password" => Hash::make("password"),
//     "role" => "super_admin",
//     "permissions" => json_encode(["view_dashboard" => true, "edit_settings" => true]),
// ]);

// // Assign roles to the admin user
// $adminRole = \App\Models\AdminRole::where("name", "super_admin")->first();
// $adminUser->roles()->attach($adminRole->id);


// Querying Roles and Permissions
// You can query the roles and permissions of an admin user as follows:

// $adminUser = \App\Models\AdminUser::find(1);

// // Get all roles assigned to the admin user
// $roles = $adminUser->roles;

// // Check if the admin user has a specific permission
// if ($adminUser->permissions && isset($adminUser->permissions["view_dashboard"]) && $adminUser->permissions["view_dashboard"]) {
//     echo "Admin has view_dashboard permission.";
// }