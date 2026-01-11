<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestedDatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public $filePath;
    public $filename;

    /**
     * Create a new message instance.
     */
    public function __construct($filePath, $filename)
    {
        $this->filePath = $filePath;
        $this->filename = $filename;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Requested Database Backup')
            ->markdown('mail.requested_database_backup')
            ->attach($this->filePath, [
                'as' => $this->filename,
            ]);
    }
}
