<?php

namespace HeyMoon\VectorTileDataProvider\Exception;

final class UnknownFormatException extends FormatSupportException
{
    public function __construct(private readonly string $extension)
    {
        parent::__construct("Unknown file extension $extension.", $this->extension);
    }
}
