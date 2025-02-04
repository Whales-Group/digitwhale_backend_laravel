<?php

namespace App\Common\Helpers;

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
}
