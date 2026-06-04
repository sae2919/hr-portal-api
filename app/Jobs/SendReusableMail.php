<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReusableMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param string $templateName
     * @param string $toEmail
     * @param array $placeholders
     * @param string|null $subjectOverride
     * @param array $attachments
     */
    public function __construct(
        public string $templateName,
        public string $toEmail,
        public array $placeholders = [],
        public ?string $subjectOverride = null,
        public array $attachments = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Decode base64 attachments if present
            $decodedAttachments = [];
            foreach ($this->attachments as $att) {
                if (isset($att['data']) && !empty($att['base64'])) {
                    $att['data'] = base64_decode($att['data']);
                }
                $decodedAttachments[] = $att;
            }

            \App\Services\MailService::sendTemplateMailSync(
                $this->toEmail,
                $this->templateName,
                $this->placeholders,
                $decodedAttachments,
                $this->subjectOverride
            );

            Log::info("Reusable Mail sent: {$this->templateName} to {$this->toEmail}");
        } catch (\Throwable $e) {
            Log::error('Reusable Mail Job failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
