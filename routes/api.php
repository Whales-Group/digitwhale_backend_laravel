<?php

use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Route;

Route::group(["prefix" => "/mail"], function () {
    Route::post("/send-otp", [MailController::class, "sendOtpMail"]);
    Route::post("/send-welcome", [MailController::class, "sendWelcomeMail"]);
});
