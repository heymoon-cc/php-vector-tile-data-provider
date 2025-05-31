<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use Brick\Geo\Geometry;
use Brick\Geo\LineString;
use Brick\Geo\MultiLineString;
use Brick\Geo\MultiPoint;
use Brick\Geo\MultiPolygon;
use Brick\Geo\Point;
use Brick\Geo\Polygon;

interface SpatialServiceInterface
{
    public function check(array $features, int $srid): array;
    public function transform(Geometry $geometry, int $srid): Geometry;
    public function transformPoint(Point $point, int $srid): Point;
    public function transformMultiPoint(MultiPoint $geometry, int $srid, ?int $parentSRID = null): MultiPoint;
    public function transformLine(LineString $line, int $srid, ?int $parentSRID = null): LineString;
    public function transformMultiLine(MultiLineString $geometry, int $srid, ?int $parentSRID = null): MultiLineString;
    public function transformPolygon(Polygon $polygon, int $srid, ?int $parentSRID = null): Polygon;
    public function transformMultiPolygon(MultiPolygon $geometry, int $srid, ?int $parentSRID = null): MultiPolygon;
}
