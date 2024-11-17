<?php

namespace App\Modules\MailModule;

use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Http\Request;

class MailModuleMain
{
    public static function sendOtpMail(Request $request): bool
    {
        $mail = new OtpMail([
            "name" => $request->first_name,
            "otp" => $request->otp,
            "greeting" => "Hi there,",
            "intro" => "Your OTP is:",
            "outro" =>
                "Please use this code within " .
                $request->len_in_min .
                " minutes.",
            "logoUrl" => asset(
                "https://res.cloudinary.com/dch8zvohv/image/upload/v1715941669/cloudinary-original/cp07qz3ydlhnaowkltg3.png",
                true
            ),
            "title" => "OTP Code",
            "companyName" => "Whales Finance",
        ]);

        $status = Mail::mailer("test_smtp")
            ->to($request->email)
            ->send($mail);

        return $status ? true : false;
    }

    public static function sendWelcomeMail(Request $request): bool
    {
        $mail = new WelcomeMail([
            "title" => "Welcome to your Financial Journey",
            "greeting" => "Welcome to Whales Finance",
            "name" => $request->first_name,
            "intro" =>
                "We're thrilled to have you join our community! Get ready to take control of your finances with our powerful tracking tools.",
            "text" =>
                "Explore our features, set financial goals, and track your progress effortlessly. We're here to support you every step of the way.",
            "outro" => "Let's build a brighter financial future together!",
            "companyName" => "Whales Finance",
        ]);

        $status = Mail::mailer("test_smtp")
            ->to($request->email)
            ->send($mail);

        return $status ? true : false;
    }
}
