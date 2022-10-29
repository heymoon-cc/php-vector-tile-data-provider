<?php

namespace HeyMoon\MVTTools\Factory;

use Exception;
use HeyMoon\MVTTools\Helper\EncodingHelper;
use Vector_tile\Tile;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TileFactory
{
    public function parse(string $data): ?Tile
    {
        $tile = new Tile();
        try {
            $tile->mergeFromString(EncodingHelper::getOriginalOrGZIP($data));
        } catch (Exception) {
            return null;
        }
        return $tile;
    }
}
