<?php

namespace App\Services\MailSender;

use Illuminate\Support\Facades\Log;

/**
 * Placeholder implementation: logs instead of calling a real mail provider.
 * Swap the binding in AppServiceProvider for MailjetMailSender when Mailjet
 * credentials are configured — callers only ever depend on MailSenderInterface.
 */
class LogMailSender implements MailSenderInterface
{
    public function send(
        string $toEmail,
        ?string $toName,
        string $fromEmail,
        ?string $fromName,
        string $subject,
        string $body,
        ?string $replyToEmail = null,
        ?string $replyToName = null,
    ): void {
        Log::info('MailSender (stub): would send email', [
            'to' => $toEmail,
            'from' => $fromEmail,
            'reply_to' => $replyToEmail,
            'subject' => $subject,
            'body' => $body,
        ]);
    }
}
