<?php

namespace HeyMoon\VectorTileDataProvider\Factory;

use Exception;
use HeyMoon\VectorTileDataProvider\Helper\EncodingHelper;
use HeyMoon\VectorTileDataProvider\Service\TileService;
use Vector_tile\Tile;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TileFactory
{
    public function __construct(private readonly TileService $tileService) {}

    public function parse(string $data, ?Tile $target = null): ?Tile
    {
        $tile = $target ?? new Tile();
        try {
            $tile->mergeFromString(EncodingHelper::getOriginalOrGZIP($data));
        } catch (Exception) {
            return null;
        }
        return $tile;
    }

    public function merge(Tile $tile, string $data): ?Tile
    {
        return $this->tileService->mergeLayers($this->parse($data, $tile));
    }
}
