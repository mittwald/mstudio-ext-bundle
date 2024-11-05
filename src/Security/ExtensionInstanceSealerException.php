<?php

namespace Mittwald\MStudio\Bundle\Security;

use Exception;
use Throwable;

class ExtensionInstanceSealerException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $message = $message . ": " . openssl_error_string();
        parent::__construct($message, $code, $previous);
    }
}