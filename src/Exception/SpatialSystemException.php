<?php

namespace HeyMoon\MVTTools\Exception;

use RuntimeException;

abstract class SpatialSystemException extends RuntimeException
{
    public function __construct(int $from, int $to)
    {
        parent::__construct("Unable to transform geometry from SRID $from to $to.");
    }
}
