<?php

namespace App\Services\EmailFinder;

interface EmailFinderInterface
{
    /**
     * Attempt to find a contact email address on the given website.
     * Returns null if none could be found.
     */
    public function find(string $url): ?string;
}
