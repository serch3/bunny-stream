<?php

declare(strict_types=1);

namespace Bunny\Stream;

class VideoNotFoundException extends Exception
{
    public function __construct(string $guid, int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct("Could not find requested video: {$guid}", $code, $previous);
    }
}