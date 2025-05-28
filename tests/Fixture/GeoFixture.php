<?php

namespace HeyMoon\VectorTileDataProvider\Tests\Fixture;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\IO\GeoJSON\Feature;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Brick\Geo\LineString;
use Brick\Geo\Point;
use Brick\Geo\Polygon;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class GeoFixture
{
    /**
     * @param int $radius
     * @param int $count
     * @return FeatureCollection
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public function getFeatureCollection(int $radius, int $count): FeatureCollection
    {
        return new FeatureCollection(...array_map(fn(int $i) => new Feature(match ($i % 3) {
            0 => $this->getPoint($i, $count, $radius),
            1 => LineString::of($this->getPoint($i, $count, $radius), Point::xy(0, 0)),
            2 => Polygon::of(LineString::of(Point::xy(0, 0),
                $this->getPoint($i - 1, $count, $radius),
                $this->getPoint($i, $count, $radius),
                Point::xy(0, 0)))
        }), range(1, $count)));
    }

    private function getPoint($i, $count, $radius): Point
    {
        return Point::xy(
            sin($i / $count * 2 * pi()) * $radius,
            cos($i / $count * 2 * pi()) * $radius);
    }
}
