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
     * @param array{name: string, website_url: ?string, brief: ?string}|null $product Optional details of
     *     the product being sold (the owning directory's product), so the copy can pitch the right thing.
     *     The generated body should mention the product's name and website URL more than once.
     * @param string $language The language to write the email in (e.g. "French").
     * @return array{name: string, subject: string, body: string}
     */
    public function generate(
        string $prompt,
        ?string $audienceContext = null,
        ?array $product = null,
        string $language = 'English'
    ): array;
}
