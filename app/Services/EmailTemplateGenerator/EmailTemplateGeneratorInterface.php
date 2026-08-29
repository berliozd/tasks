<?php

namespace App\Services\EmailTemplateGenerator;

interface EmailTemplateGeneratorInterface
{
    /**
     * Generate a single email template from a free-text prompt.
     *
     * @param string $prompt What the email should accomplish (e.g. "cold intro offering a free trial").
     * @param string|null $audienceContext Optional description of who the email is going to
     *     (typically the directory's own prospecting prompt), so the copy can be tailored to them.
     * @return array{name: string, subject: string, body: string}
     */
    public function generate(string $prompt, ?string $audienceContext = null): array;
}
