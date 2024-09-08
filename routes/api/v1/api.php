<?php

use Illuminate\Support\Facades\Route;
use App\Common\Enums\TokenAbility;

Route::middleware(["VerifyApiKey"])->group(function () { });

Route::middleware([
    "VerifyApiKey",
    "auth:sanctum",
    "ability:" . TokenAbility::ACCESS_API->value,
])->group(function () { });

Route::middleware([
    "VerifyApiKey",
    "auth:sanctum",
    "ability:" . TokenAbility::ISSUE_ACCESS_TOKEN->value,
])->group(function () {

});

Route::get('example', function () {
    return 'Test'; });
