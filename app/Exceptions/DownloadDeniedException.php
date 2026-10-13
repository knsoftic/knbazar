<?php

namespace App\Exceptions;

use RuntimeException;

class DownloadDeniedException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $requiresLogin = false)
    {
        parent::__construct($message);
    }
}
