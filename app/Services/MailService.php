<?php

namespace App\Services;

use App\Models\MailTemplate;
use App\Mail\DynamicMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * Compile and send a mail template if it exists and is active.
     *
     * @param string $toEmail
     * @param string $templateName
     * @param array $variables
     * @return bool
     */
    public static function sendTemplateMail(string $toEmail, string $templateName, array $variables = [], array $attachments = []): bool
    {
        try {
            $template = MailTemplate::where('template_name', $templateName)
                                    ->where('active_status', 1)
                                    ->first();

            if (!$template) {
                Log::info("MailTemplate '{$templateName}' not found or inactive. Skipping email sending.");
                return false;
            }

            // Perform bracket replacements in Subject and Body
            $compiledSubject = $template->subject ?? '';
            $compiledBody = $template->body ?? '';

            // Add standard global variables if not already present
            if (!isset($variables['current_date'])) {
                $variables['current_date'] = date('Y-m-d');
            }

            foreach ($variables as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $valStr = is_scalar($value) ? (string) $value : '';
                $compiledSubject = str_replace($placeholder, $valStr, $compiledSubject);
                $compiledBody = str_replace($placeholder, $valStr, $compiledBody);
            }

            // Fetch company details for global header/footer (Way 2)
            $companyName = \App\Models\CompanySetting::getValue('company_name') ?? 'Techsprout';
            $companyLogo = \App\Models\CompanySetting::getValue('company_logo') ?? null;
            
            $logoHtml = '';
            if ($companyLogo) {
                $logoHtml = "<img src='{$companyLogo}' alt='{$companyName}' style='max-height: 45px; margin-bottom: 8px;' />";
            }

            // Wrap body inside HTML container with styles
            $finalHtml = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    /* Global Base Email Styles */
                    body {
                        margin: 0;
                        padding: 20px;
                        background-color: #f8fafc;
                        font-family: 'Outfit', 'Inter', Arial, sans-serif;
                    }
                    .email-wrapper {
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border-radius: 16px;
                        border: 1px solid #e2e8f0;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                        overflow: hidden;
                    }
                    .global-header {
                        background-color: #0f172a;
                        padding: 24px;
                        text-align: center;
                        color: #ffffff;
                        border-bottom: 1px solid #e2e8f0;
                    }
                    .global-header h1 {
                        margin: 0;
                        font-size: 18px;
                        font-weight: 700;
                        letter-spacing: -0.5px;
                    }
                    .email-body {
                        padding: 30px;
                        color: #334155;
                        font-size: 15px;
                        line-height: 1.6;
                    }
                    .global-footer {
                        background-color: #f8fafc;
                        padding: 20px;
                        text-align: center;
                        color: #64748b;
                        font-size: 11px;
                        border-top: 1px solid #e2e8f0;
                    }
                    .global-footer p {
                        margin: 4px 0;
                    }

                    /* Custom Template CSS Overrides */
                    " . ($template->style ?? '') . "
                </style>
            </head>
            <body>
                <div class='email-wrapper'>
                    <div class='global-header'>
                        {$logoHtml}
                        <h1>{$companyName}</h1>
                    </div>
                    <div class='email-body'>
                        {$compiledBody}
                    </div>
                    <div class='global-footer'>
                        <p>This is an automated notification from {$companyName}.</p>
                        <p>&copy; " . date('Y') . " {$companyName}. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            Mail::to($toEmail)->send(new DynamicMail($compiledSubject, $finalHtml, $attachments));
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send template mail '{$templateName}' to '{$toEmail}': " . $e->getMessage());
            return false;
        }
    }
}
