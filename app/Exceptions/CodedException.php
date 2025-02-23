<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

class CodedException extends Exception
{
    protected ErrorCode $errorCode;

    public function __construct(ErrorCode $errorCode, ?string $message = null)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message ?? $errorCode->message(), $errorCode->code());
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }
}