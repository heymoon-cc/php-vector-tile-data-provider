<?php

namespace HeyMoon\VectorTileDataProvider\Export;

use HeyMoon\VectorTileDataProvider\Contract\TileServiceInterface;
use Vector_tile\Tile;

class MvtExportFormat extends AbstractExportFormat
{
    protected static function defaultSupports(): array
    {
        return ['mvt', 'pbf'];
    }

    public function export(TileServiceInterface $service, Tile $tile, callable|string|null $color = null): object|string
    {
        return $tile->serializeToString();
    }
}
