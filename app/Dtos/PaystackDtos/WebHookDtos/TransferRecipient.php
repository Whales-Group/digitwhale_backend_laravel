<?php
namespace App\Dtos\PaystackDtos\WebHookDtos;

class TransferRecipient
{
    public bool $active;
    public string $currency;
    public ?string $description;
    public string $domain;
    public ?string $email;
    public int $id;
    public int $integration;
    public ?array $metadata;
    public string $name;
    public string $recipient_code;
    public string $type;
    public bool $is_deleted;
    public TransferDetails $details;
    public string $created_at;
    public string $updated_at;
}
