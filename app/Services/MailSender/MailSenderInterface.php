<?php

namespace App\Services\MailSender;

use Exception;

interface MailSenderInterface
{
    /**
     * @throws Exception
     */
    public function send(
        string $toEmail,
        ?string $toName,
        string $fromEmail,
        ?string $fromName,
        string $subject,
        string $body,
        ?string $replyToEmail = null,
        ?string $replyToName = null,
        ?string $htmlBody = null,
    ): void;
}
