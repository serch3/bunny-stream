<?php

declare(strict_types=1);

namespace Bunny\Stream;

class AuthenticationException extends Exception
{
    public function __construct(string $accessKey, int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct("Authentication denied for access key '{$accessKey}'.", $code, $previous);
    }
}