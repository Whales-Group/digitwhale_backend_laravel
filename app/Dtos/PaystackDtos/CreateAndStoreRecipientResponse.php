<?php

namespace App\Dtos\PaystackDtos;

class CreateAndStoreRecipientResponse
{
    protected bool $status;
    protected string $message;
    protected string $data;

    public function __construct(bool $status, string $message, mixed $data)
    {
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }
}
