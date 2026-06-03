<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DynamicMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    public string $htmlContent;
    public array $attachmentsList;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subjectLine, string $htmlContent, array $attachmentsList = [])
    {
        $this->subjectLine = $subjectLine;
        $this->htmlContent = $htmlContent;
        $this->attachmentsList = $attachmentsList;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $mail = $this->html($this->htmlContent)
                     ->subject($this->subjectLine);

        foreach ($this->attachmentsList as $att) {
            if (isset($att['data'])) {
                $mail->attachData($att['data'], $att['name'], [
                    'mime' => $att['mime'] ?? 'application/pdf',
                ]);
            } elseif (isset($att['path']) && file_exists($att['path'])) {
                $mail->attach($att['path'], [
                    'as' => $att['name'],
                    'mime' => $att['mime'] ?? 'application/pdf',
                ]);
            }
        }

        return $mail;
    }
}
