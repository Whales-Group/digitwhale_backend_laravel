<?php

namespace App\Common\Helpers;

use App\Common\Enums\Cred;

class CodeHelper
{
    /**
     * Generate a random Code of specified length.
     *
     * @param int $length
     * @return string
     */
    public static function generate(
        int $length,
        bool $numbersOnly = false
    ): string {
        $characters = "";
        if ($numbersOnly) {
            $characters = "0123456789";
        } else {
            $characters =
                "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        }

        $otp = "";

        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $otp;
    }

    public static function generateSecureReference(): string
    {
        $prefix = Cred::ALT_COMPANY_NAME->value . "-";
        $timestamp = microtime(true);
        $randomNumber = rand(0, 1000000);
        $userId = auth()->id();
        $hash = hash('sha256', $timestamp . $randomNumber . $userId);
        $secureReference = substr($hash, 0, 14);

        return $prefix . $secureReference;
    }

    public static function extractErrorMessage($error)
    {
        if (is_string($error)) {
            return $error;
        }

        if (is_array($error)) {
            return $error['message'] ?? 'An unknown error occurred.';
        }

        if (is_object($error)) {
            return $error->message ?? $error->getMessage() ?? 'An unknown error occurred.';
        }

        return 'An unknown error occurred.';
    }
}
