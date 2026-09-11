<?php

namespace App\Services;

use App\Models\User;
use App\Services\MailSender\MailSenderInterface;
use Exception;

readonly class FeatureRequestService
{
    public function __construct(
        private MailSenderInterface $mailSender,
    ) {
    }

    /**
     * @throws Exception
     */
    public function submit(User $user, string $message): void
    {
        $developerEmail = (string) config('services.developer.email');

        if (empty($developerEmail)) {
            throw new Exception('No developer contact email is configured (set DEVELOPER_EMAIL)');
        }

        $this->mailSender->send(
            $developerEmail,
            null,
            (string) config('mail.from.address'),
            (string) config('mail.from.name'),
            "Feature request from {$user->name}",
            $message,
            $user->email,
            $user->name,
        );
    }
}
