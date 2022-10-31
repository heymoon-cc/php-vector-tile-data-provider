<?php

namespace HeyMoon\VectorTileDataProvider\Spatial;

use Brick\Geo\Geometry;
use Brick\Geo\Point;

interface SpatialProjectionInterface
{
    public static function get(?int $srid = null): self;

    public function isAligned(Geometry $geometry): bool;

    public function getSRID(): int;

    public function toWGS84(Point $point): Point;

    public function fromWGS84(Point $point): Point;
}
