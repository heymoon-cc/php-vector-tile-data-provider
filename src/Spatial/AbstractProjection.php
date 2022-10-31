<?php

namespace HeyMoon\VectorTileDataProvider\Spatial;

use Brick\Geo\Geometry;
use Brick\Geo\Point;

abstract class AbstractProjection implements SpatialProjectionInterface
{
    public const SRID = 0;

    private function __construct(private readonly int $srid) {}

    public static function get(?int $srid = null): SpatialProjectionInterface
    {
        return new static($srid ?? static::SRID);
    }

    public function getSRID(): int
    {
        return $this->srid;
    }

    public function isAligned(Geometry $geometry): bool
    {
        return $geometry->SRID() === $this->getSRID();
    }

    public function toWGS84(Point $point): Point
    {
        return Point::xy($this->longitudeToWGS84($point->x()), $this->latitudeToWGS84($point->y()))
            ->withSRID(WorldGeodeticProjection::SRID);
    }

    public function fromWGS84(Point $point): Point
    {
        return Point::xy($this->longitudeFromWGS84($point->x()), $this->latitudeFromWGS84($point->y()))
            ->withSRID($this->getSRID());
    }

    protected abstract function longitudeToWGS84(float $long): float;
    protected abstract function latitudeToWGS84(float $lat): float;
    protected abstract function longitudeFromWGS84(float $long): float;
    protected abstract function latitudeFromWGS84(float $lat): float;
}
