<?php

namespace HeyMoon\VectorTileDataProvider\Exception;

use RuntimeException;

abstract class FormatSupportException extends RuntimeException implements SupportExceptionInterface
{
    public function __construct(string $message, private readonly string $extension)
    {
        parent::__construct($message);
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getRequirements(): array
    {
        return [];
    }
}
