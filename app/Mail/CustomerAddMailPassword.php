<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerAddMailPassword extends Mailable
{
    use Queueable, SerializesModels;

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
        $this->systemname = $body['systemname'];
        $this->password = $body['password'];
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
                    ->view('emails.customerAddPassword')
                     ->with([
                    'name' => $this->name,
                    'person' => $this->person,
                    'systemname' => $this->systemname,
                    'url' => $this->app_url,
                    'password' => $this->password,
                    'invgfoot' => $this->invgfoot,
                    ]);
    }
}
