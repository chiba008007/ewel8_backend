<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PdfDownloadMail extends Mailable
{
    use Queueable;
    use SerializesModels;

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
        $this->testname = $body['testname'];
        $this->person = $body['person'];
        $this->uploadFileMail = $body['uploadFileMail'];
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
                    ->view('emails.pdfDownload')
                     ->with([
                    'name' => $this->name,
                    'person' => $this->person,
                    'testname' => $this->testname,
                    'uploadFileMail' => $this->uploadFileMail,
                    'baseURL' => env('APP_URL'),
                    'invgfoot' => $this->invgfoot,
                    ]);
    }
}
