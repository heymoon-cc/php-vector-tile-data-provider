<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use HeyMoon\VectorTileDataProvider\Entity\TilePosition;
use Vector_tile\Tile;

interface TileServiceInterface
{
    public const DEFAULT_EXTENT = 4096;
    public function getTileMVT(
        array $shapes,
        TilePosition $position,
        int $extent = self::DEFAULT_EXTENT,
        ?float $buffer = null
    ): Tile;
    public function getExtent(Tile $tile): int;
    public function mergeLayers(Tile $tile): Tile;
    public function getValues(Tile\Layer $layer, Tile\Feature $feature): array;
    public function decodeGeometry(Tile\Layer $layer, TilePosition $position): LayerInterface;
}
