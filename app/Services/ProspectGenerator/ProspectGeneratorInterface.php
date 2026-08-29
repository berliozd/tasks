<?php

namespace App\Services\ProspectGenerator;

interface ProspectGeneratorInterface
{
    /**
     * Generate a list of prospect rows from a free-text prompt.
     *
     * @return array<int, array{name: string, website: ?string, email: ?string}>
     */
    public function generate(string $prompt, int $count): array;
}
