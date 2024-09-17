<?php

namespace App\Models;

use App\Repositories\PaystackRepository;
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

    public function getFullName(): string
    {
        return $this->first_name . " " . $this->last_name;
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
            // Create a new tag for the user
            Tag::create([
                "tag" => request()->tag,
                "user_id" => $user->id,
            ]);

            Account::create([
                "user_id" => $user->id,
                // "account_number" => self::generateUniqueBankAccountNumber(),
                "account_number" => self::resolveUser($user),
                "account_name" => $user->fullName,
                "balance" => 0.0,
                "type" => "tire1",
            ]);
        });
    }

    /**
     * Create Paystack Customer
     * Generate DVA
     *
     * @return string
     */
    private static function resolveUser(User $user): ?string
    {
        $paystackRepo = new PaystackRepository();
        // Create Paystack Customer
        $paystackCustomer = $paystackRepo->createAndSaveCustomer($user);

        // Create DVA
        // $paystackDVA = $paystackRepo->generateDVA(
        //     $paystackCustomer->customer_code
        // );
        //
        $paystackDVA = $paystackRepo->testGenerateDVA(
            $user,
            $paystackCustomer->customer_code
        );

        return $paystackDVA->account_number;
    }

    /**
     * Generate a unique 11-digit bank account number (TEST).
     *
     * @return string The generated unique 11-digit bank account number (TEST).
     */
    static function generateUniqueBankAccountNumber(): string
    {
        do {
            $randomNumber = mt_rand(0, 9999999999);
            $accountNumber = str_pad($randomNumber, 11, "0", STR_PAD_LEFT);

            $exists = Account::where(
                "account_number",
                $accountNumber
            )->exists();
        } while ($exists);

        return $accountNumber;
    }
}
