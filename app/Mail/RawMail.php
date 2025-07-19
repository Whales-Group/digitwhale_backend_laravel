<?php


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RawMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $fromName;
    public string $fromEmail;
    public string $subjectText;
    public string $body;

    public function __construct($fromName, $fromEmail, $subjectText, $body)
    {
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
        $this->subjectText = $subjectText;
        $this->body = $body;
    }

    public function build()
    {
        return $this
            ->from($this->fromEmail, $this->fromName)
            ->subject($this->subjectText)
            ->html("<p>{$this->body}</p>");
    }
}
