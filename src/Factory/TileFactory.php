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
    /**
     * @throws Exception
     */
    public function parse(string $data): Tile
    {
        $tile = new Tile();
        $tile->mergeFromString(EncodingHelper::getOriginalOrGZIP($data));
        return $tile;
    }
}
