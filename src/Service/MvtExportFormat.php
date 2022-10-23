<?php

namespace HeyMoon\MVTTools\Service;

use HeyMoon\MVTTools\Export\AbstractExportFormat;
use Vector_tile\Tile;

class MvtExportFormat extends AbstractExportFormat
{
    protected static function defaultSupports(): array
    {
        return ['mvt', 'pbf'];
    }

    public function export(TileService $service, Tile $tile, callable|string|null $color = null): object|string
    {
        return $tile->serializeToString();
    }
}
