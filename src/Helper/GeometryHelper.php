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

class GeometryHelper
{
    private static array $grids = [];
    private static array $widths = [];

    /**
     * @throws EmptyGeometryException
     */
    public static function getLineBounds(LineString $line): array
    {
        $minX = $maxX = $line->startPoint()->x();
        $minY = $maxY = $line->startPoint()->y();
        foreach (array_slice($line->points(), 1) as $point) {
            $minX = min($point->x(), $minX);
            $minY = min($point->y(), $minY);
            $maxX = max($point->x(), $maxX);
            $maxY = max($point->y(), $maxY);
        }
        return [
            Point::xy($minX, $minY, WebMercatorProjection::SRID),
            Point::xy($maxX, $maxY, WebMercatorProjection::SRID)
        ];
    }

    /**
     * @return Point[]
     * @throws EmptyGeometryException
     */
    public static function getBounds(Polygon $polygon): array
    {
        return static::getLineBounds($polygon->exteriorRing());
    }

    public static function getGridSize(int $zoom): int
    {
        return static::$grids[$zoom] ?? (static::$grids[$zoom] = (int)pow(2, $zoom));
    }

    public static function getTileWidth(int $zoom): float
    {
        return static::$widths[$zoom] ??
            (static::$widths[$zoom] = array_reduce($zoom >= 1 ? range(1, $zoom) : [],
                fn (float $width) => $width / 2,
                WebMercatorProjection::EARTH_RADIUS * 2)
            );
    }

    /**
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public static function getTileBorder(TilePosition $position): Polygon
    {
        $column = $position->getColumn();
        $row = $position->getRow();
        $tileWidth = $position->getTileWidth();
        return Polygon::of(LineString::of(
            Point::xy($column * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                $row * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                WebMercatorProjection::SRID
            ),
            Point::xy(
                ($column + 1) * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                $row * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                WebMercatorProjection::SRID
            ),
            Point::xy(
                ($column + 1) * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                ($row + 1) * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                WebMercatorProjection::SRID
            ),
            Point::xy(
                $column * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                ($row + 1) * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                WebMercatorProjection::SRID
            ),
            Point::xy(
                $column * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                $row * $tileWidth
                - WebMercatorProjection::EARTH_RADIUS,
                WebMercatorProjection::SRID
            )
        ));
    }
}
