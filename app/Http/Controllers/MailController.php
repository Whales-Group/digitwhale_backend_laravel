<?php

namespace App\Http\Controllers;


use App\Services\MailService;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public  function sendOtpMail(Request $request)
    {
        return MailService::sendOtpMail($request);

    }

    public  function sendWelcomeMail(Request $request): bool
    {
        return MailService::sendWelcomeMail($request);
    }
}
