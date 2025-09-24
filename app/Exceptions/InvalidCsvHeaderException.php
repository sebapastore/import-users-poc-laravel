<?php

namespace App\Exceptions;

use Exception;

class InvalidCsvHeaderException extends Exception
{
    public function __construct(string $expected, string $actual, int $index, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Expected header '{$expected}' at column {$index}, got '{$actual}'";
        parent::__construct($message, $code, $previous);
    }
}
