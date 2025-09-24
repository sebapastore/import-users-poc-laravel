<?php

namespace App\Exceptions;

use Exception;

class FileOpenException extends Exception
{
    public function __construct(string $filePath, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Could not open file: {$filePath}";
        parent::__construct($message, $code, $previous);
    }
}
