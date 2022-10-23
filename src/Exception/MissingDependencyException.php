<?php

namespace HeyMoon\MVTTools\Exception;

final class MissingDependencyException extends FormatSupportException
{
    public function __construct(string $extension, private readonly array $requirements = [])
    {
        $missing = $this->requirements ? ' Missing requirements: '.implode(', ', $this->requirements).'.' : '';
        parent::__construct("Unsupported file extension $extension.$missing", $extension);
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }
}
