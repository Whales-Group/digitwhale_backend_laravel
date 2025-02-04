<?php

namespace App\Http\Controllers;

use App\Modules\MailModule\MailModuleMain;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function sendOtpMail(Request $request): bool
    {
        return MailModuleMain::sendOtpMail($request);
    }

    public function sendWelcomeMail(Request $request): bool
    {
        return MailModuleMain::sendWelcomeMail($request);
    }
}
