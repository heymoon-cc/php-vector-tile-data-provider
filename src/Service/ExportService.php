<?php

namespace HeyMoon\MVTTools\Service;

use HeyMoon\MVTTools\Registry\AbstractExportFormatRegistry;
use Vector_tile\Tile;

class ExportService
{
    public function __construct(
        private readonly AbstractExportFormatRegistry $factory,
        private readonly TileService $service
    ) {}

    public function dump(Tile $tile, string $path, string|callable|null $color = null): void
    {
        file_put_contents($path, (string)$this->factory->byPath($path)->export($this->service, $tile, $color));
    }

    public function convert(Tile $tile, string|callable|null $color = null, string $ext = 'svg'): object|string
    {
        return $this->factory->get($ext)->export($this->service, $tile, $color);
    }
}
