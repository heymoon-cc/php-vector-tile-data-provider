<?php

namespace HeyMoon\MVTTools\Helper;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\LineString;
use Brick\Geo\Point;
use Brick\Geo\Polygon;
use HeyMoon\MVTTools\Spatial\WebMercatorProjection;
use HeyMoon\MVTTools\Entity\TilePosition;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class GeometryHelper
{
    private static array $grids = [];

    public static function getGridSize(int $zoom): int
    {
        return static::$grids[$zoom] ?? (static::$grids[$zoom] = (int)pow(2, $zoom));
    }

    public static function getTileWidth(int $zoom): float
    {
        return (WebMercatorProjection::EARTH_RADIUS * 2) / static::getGridSize($zoom);
    }
}
