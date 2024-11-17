<?php

namespace App\Repositories;

class PaystackRepository
{
    protected $description = "Paystack Repository - (Contains methods for payments, transaction verification, customer management)";

    protected string $baseUrl = "https://api.paystack.co/";

    protected array $headers;

    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = env("PAYSTACK_TEST_SK", "");
        $this->headers = [
            "Authorization" => "Bearer " . $this->secretKey,
            "Content-Type" => "application/json",
            "Cache-Control: no-cache",
        ];
    }
}