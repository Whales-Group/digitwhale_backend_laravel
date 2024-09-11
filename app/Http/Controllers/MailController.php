<?php

namespace App\Http\Controllers;

use App\Modules\MailModule\MailModule;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function sendOtpMail(Request $request): bool
    {
        return MailModule::sendOtpMail($request);
    }

    public function sendWelcomeMail(Request $request): bool
    {
        return MailModule::sendWelcomeMail($request);
    }
}
