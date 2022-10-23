<?php

namespace HeyMoon\MVTTools\Export;

abstract class AbstractExportFormat implements ExportFormatInterface
{
    private function __construct(private readonly array $supports) {}

    public static function get(array $supports = []): static
    {
        return new static(array_unique(array_merge(static::defaultSupports(), $supports)));
    }

    public function supports(): array
    {
        return $this->supports;
    }

    public function isAvailable(): bool
    {
        $class = $this->getDependencyClass();
        return is_null($class) || class_exists($class);
    }

    public function require(): array
    {
        return [];
    }

    protected function getDependencyClass(): ?string
    {
        return null;
    }

    protected abstract static function defaultSupports(): array;
}
