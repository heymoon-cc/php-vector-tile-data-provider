<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use Vector_tile\Tile;

interface ExportServiceInterface
{
    public function dump(Tile $tile, string $path, string|callable|null $color = null): void;
    public function convert(Tile $tile, string|callable|null $color = null, string $ext = 'svg'): object|string;
}
