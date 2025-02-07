<?php

namespace App\Dtos\PaystackDtos\WebHookDtos;

class TransferData
{
    public int $amount;
    public string $currency;
    public string $domain;
    public ?array $failures;
    public int $id;
    public TransferIntegration $integration;
    public string $reason;
    public string $reference;
    public string $source;
    public ?array $source_details;
    public string $status;
    public ?string $titan_code;
    public string $transfer_code;
    public ?string $transferred_at;
    public TransferRecipient $recipient;
    public TransferSession $session;
    public string $created_at;
    public string $updated_at;
}
