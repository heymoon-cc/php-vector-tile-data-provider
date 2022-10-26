<?php

namespace HeyMoon\MVTTools\Exception;

use RuntimeException;

abstract class SpatialSystemException extends RuntimeException
{
    public function __construct(int $source, int $target)
    {
        parent::__construct("Unable to transform geometry from SRID $source to $target.");
    }
}
