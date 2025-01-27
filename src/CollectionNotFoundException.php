<?php

declare(strict_types=1);

namespace Bunny\Stream;

class CollectionNotFoundException extends Exception
{
    public function __construct(string $collectionId, int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct("The requested collection was not found: {$collectionId}", $code, $previous);
    }
}