<?php

namespace App\Modules\MailModule\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;

class RawMailerService
{
 public static function send(array $mailData): void
 {
  $smtpConfig = [
   'transport' => 'smtp',
   'host' => $mailData['smtp_host'] ?? env('MAIL_HOST'),
   'port' => $mailData['smtp_port'] ?? env('MAIL_PORT', 587),
   'encryption' => $mailData['smtp_encryption'] ?? env('MAIL_ENCRYPTION', 'tls'),
   'username' => $mailData['smtp_username'] ?? env('MAIL_USERNAME'),
   'password' => $mailData['smtp_password'] ?? env('MAIL_PASSWORD'),
   'timeout' => null,
   'local_domain' => env('MAIL_EHLO_DOMAIN'),
   'from' => [
    'address' => $mailData['from_email'],
    'name' => $mailData['from_name'],
   ],
  ];

  Mail::build($smtpConfig)
   ->to($mailData['to'])
   ->send(new class ($mailData) extends Mailable {
   public function __construct(private array $mailData)
   {
    // 👇 Set the "from" header directly
    $this->from($this->mailData['from_email'], $this->mailData['from_name']);
   }

   public function envelope(): Envelope
   {
    return new Envelope(
     subject: $this->mailData['subject']
    );
   }

   public function content(): Content
   {
    return new Content(
     view: 'emails.generic',
     with: [
      'subject' => $this->mailData['subject'],
      'html' => $this->mailData['html'],
     ]
    );
   }
   });
 }
}
