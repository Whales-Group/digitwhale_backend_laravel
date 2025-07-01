<?php

namespace App\Models;

use App\Enums\ServiceProvider;
use App\Models\Account;
use App\Modules\AccountSettingModule\Services\AccountSettingsCreationService;
use App\Modules\TransferModule\Services\TransactionService;
use App\Modules\UtilsModule\Services\PackageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        "first_name",
        "last_name",
        "middle_name",
        "business_name",
        "email",
        "tag",
        "password",
        "email_verified_at",
        "date_of_birth",
        "profile_url",
        "other_url",
        "phone_number",
        "profile_type",
        "gender",
        "nin",
        "bvn",
        "marital_status",
        "employment_status",
        "annual_income",
        "country_iso",
        "state_province",
        "city",
        "street_address",
        "street_number",
    ];

    public static $promptProtect = [
        "nin",
        "bvn",
        "password",
        "email_verified_at",
    ];

    protected $hidden = [
        "password",
        "remember_token",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        "date_of_birth" => "date",
    ];

    public function fullName(): string
    {
        return "{$this->first_name} {$this->middle_name} {$this->last_name}";
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    protected static function booted()
    {
        static::created(function () {
            $packageService = new PackageService(new TransactionService());
            $packageService->subscribe('Basic');
        });
    }

    public function profileIsCompleted(ServiceProvider $serviceProvider): array
    {
        if ($serviceProvider == ServiceProvider::FINCRA) {
            $profileFields = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'date_of_birth' => $this->date_of_birth,
                'phone_number' => $this->phone_number,
                'gender' => $this->gender,
                'bvn' => $this->bvn,
            ];
        } elseif ($serviceProvider == ServiceProvider::PAYSTACK) {
            $profileFields = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone_number' => $this->phone_number,
            ];
        } else {
            $profileFields = [];
        }

        $fieldNames = [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'date_of_birth' => 'Date of birth',
            'phone_number' => 'Phone number',
            'gender' => 'Gender',
            'bvn' => 'BVN',
        ];

        $missingFields = array_filter($profileFields, fn($value) => is_null($value));
        $isCompleted = empty($missingFields);

        $message = $isCompleted
            ? 'Profile is complete.'
            : implode(', ', array_map(fn($key) => $fieldNames[$key] . ' not set', array_keys($missingFields)));

        return [
            "bool" => $isCompleted,
            "message" => $message,
        ];
    }

    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function liveLocation()
    {
        return $this->hasOne(LiveLocation::class);
    }

    public function blockedLiveLocationUsers()
    {
        return $this->hasOne(BlockedLiveLocationUsers::class);
    }

    public function liveLocationPreference()
    {
        return $this->hasOne(LiveLocationPreference::class);
    }

     public function account()
    {
        return $this->hasMany(Account::class);
    }



}