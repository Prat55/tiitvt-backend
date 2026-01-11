<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $filePath;
    public string $fileName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $filePath, string $fileName)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Daily Database Backup')
            ->view('mail.database_backup')
            ->with([
                'filename' => $this->fileName,
            ])
            ->attach($this->filePath, [
                'as' => $this->fileName,
                'mime' => 'application/zip',
            ]);
    }
}
