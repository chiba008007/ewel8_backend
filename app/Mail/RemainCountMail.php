<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RemainCountMail extends Mailable
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
        $this->rest = $body['rest'];
        $this->testname = $body['testname'];
        $this->startdate = $body['startdate'];
        $this->enddate = $body['enddate'];

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
                    ->view('emails.remainCount')
                     ->with([
                    'name' => $this->name,
                    'person' => $this->person,
                    'rest' => $this->rest,
                    'testname' => $this->testname,
                    'startdate' => $this->startdate,
                    'enddate' => $this->enddate,
                    'invgfoot' => $this->invgfoot,
                    ]);
    }
}
