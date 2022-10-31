<?php

namespace HeyMoon\VectorTileDataProvider\Exception;

use Throwable;

interface SupportExceptionInterface extends Throwable
{
    public function getExtension(): string;

    public function getRequirements(): array;
}
