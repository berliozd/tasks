<?php

namespace App\Services\MailSender;

use Exception;
use Mailjet\Client;
use Mailjet\Resources;

class MailjetMailSender implements MailSenderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
    ) {
    }

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
    ): void {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new Exception('Mailjet API credentials are not configured');
        }

        $client = new Client($this->apiKey, $this->apiSecret, true, ['version' => 'v3.1']);

        $message = [
            'From' => array_filter(['Email' => $fromEmail, 'Name' => $fromName]),
            'To' => [array_filter(['Email' => $toEmail, 'Name' => $toName])],
            'Subject' => $subject,
            'TextPart' => $body,
            'HTMLPart' => $htmlBody ?? nl2br(e($body)),
        ];

        if (!empty($replyToEmail)) {
            $message['ReplyTo'] = array_filter(['Email' => $replyToEmail, 'Name' => $replyToName]);
        }

        $response = $client->post(Resources::$Email, ['body' => [
            'Messages' => [$message],
        ]]);

        if (!$response->success()) {
            throw new Exception('Mailjet send failed: ' . json_encode($response->getData()));
        }
    }
}
