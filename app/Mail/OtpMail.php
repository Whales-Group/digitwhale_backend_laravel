<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;



    public $otp;
    public $greeting;
    public $name;
    public $intro;
    public $outro;
    public $title;


    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {

        $this->otp = $data['otp'];
        $this->greeting = $data['greeting'];
        $this->name = $data['name'];
        $this->intro = $data['intro'];
        $this->outro = $data['outro'];
        $this->title = $data['title'];
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Otp Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'otp',
            with: [
                'title' => $this->title,
                'otp' => $this->otp,
                'greeting' => $this->greeting,
                'name' => $this->name,
                'intro' => $this->intro,
                'outro' => $this->outro,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
