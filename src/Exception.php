<?php

declare(strict_types=1);

namespace Bunny\Stream;

class Exception extends \Exception
{
    public function __toString(): string
    {
        return __CLASS__.": {$this->message}\n";
    }
}