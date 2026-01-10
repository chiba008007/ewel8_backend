<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    protected string $adminMail;
    protected string $app_url;
    protected string $invgfoot;
    protected string $title;
    protected string $name;
    protected string $person;
    protected string $code;

    /**
     * Create a new message instance.
     */
    public function __construct($body)
    {
        $this->adminMail = config("const.consts.adminMail");
        $this->app_url = config("const.my-app.my-env");
        $this->invgfoot = config("const.my-app.invgfoot");
        $this->title = $body['title'];
        $this->name = $body['name'];
        $this->person = $body['person'];
        $this->code = $body['code'];
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

    public function build()
    {

        return $this->from($this->adminMail)
                    ->subject($this->title)
                    ->view('emails.twoFactor')
                     ->with([
                    'name' => $this->name,
                    'person' => $this->person,
                    'code' => $this->code,
                    'invgfoot' => $this->invgfoot,
                    ]);
    }
}
