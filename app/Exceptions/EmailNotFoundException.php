<?php

namespace App\Exceptions;

use Exception;

/**
 * A search genuinely found nothing — not a failure, so the controller maps
 * it to a 404 instead of the generic 500 every other thrown Exception gets.
 */
class EmailNotFoundException extends Exception
{
}
