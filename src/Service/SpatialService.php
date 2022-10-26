<?php

namespace HeyMoon\MVTTools\Service;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\LineString;
use Brick\Geo\MultiLineString;
use Brick\Geo\MultiPoint;
use Brick\Geo\MultiPolygon;
use Brick\Geo\Point;
use Brick\Geo\Polygon;
use HeyMoon\MVTTools\Entity\Shape;
use HeyMoon\MVTTools\Exception\SpatialSystemDecodeException;
use HeyMoon\MVTTools\Exception\SpatialSystemEncodeException;
use HeyMoon\MVTTools\Registry\AbstractProjectionRegistry;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class SpatialService
{
    public function __construct(private readonly AbstractProjectionRegistry $projectionRegistry) {}

    /**
     * @param Shape[] $shapes
     * @param int $srid
     * @return Shape[]
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     */
    public function check(array $shapes, int $srid): array
    {
        return array_map(fn(Shape $shape) =>
            $shape->getGeometry()->SRID() === $srid ? $shape : $shape->setGeometry(
            $this->transform($shape->getGeometry(), $srid)),
            $shapes
        );
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     */
    public function transform(Geometry $geometry, int $srid): Geometry
    {
        if ($this->projectionRegistry->get($srid)->isAligned($geometry)) {
            return $geometry;
        }
        if ($geometry instanceof Point) {
            return $this->transformPoint($geometry, $srid);
        } elseif ($geometry instanceof MultiPoint) {
            return $this->transformMultiPoint($geometry, $srid);
        } elseif ($geometry instanceof LineString) {
            return $this->transformLine($geometry, $srid);
        } elseif ($geometry instanceof MultiLineString) {
            return $this->transformMultiLine($geometry, $srid);
        } elseif ($geometry instanceof Polygon) {
            return $this->transformPolygon($geometry, $srid);
        } elseif ($geometry instanceof MultiPolygon) {
            return $this->transformMultiPolygon($geometry, $srid);
        }
        return $geometry;
    }

    public function transformPoint(Point $point, int $srid): Point
    {
        $target = $this->projectionRegistry->get($srid);
        if (!$target) {
            throw new SpatialSystemEncodeException($point->SRID(), $srid);
        }
        if ($target->isAligned($point)) {
            return $point;
        }
        $universal = $point->SRID() === WorldGeodeticProjection::SRID ? $point :
            ($this->projectionRegistry->get($point->SRID())?->toWGS84($point));
        if (!$universal) {
            throw new SpatialSystemDecodeException($point->SRID(), $srid);
        }
        if ($srid === WorldGeodeticProjection::SRID) {
            return $universal;
        }
        return $target->fromWGS84($universal);
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function transformMultiPoint(MultiPoint $geometry, int $srid): MultiPoint
    {
        if ($this->projectionRegistry->get($srid)->isAligned($geometry)) {
            return $geometry;
        }
        return MultiPoint::of(
            ...array_map(fn(Point $point) => $this->transformPoint($point, $srid), $geometry->geometries())
        );
    }

    /**
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public function transformLine(LineString $line, int $srid): LineString
    {
        if ($this->projectionRegistry->get($srid)->isAligned($line)) {
            return $line;
        }
        return LineString::of(...array_map(fn (Point $point) => $this->transformPoint($point, $srid), $line->points()));
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws InvalidGeometryException
     */
    public function transformMultiLine(MultiLineString $geometry, int $srid): MultiLineString
    {
        if ($this->projectionRegistry->get($srid)->isAligned($geometry)) {
            return $geometry;
        }
        return MultiLineString::of(
            ...array_map(fn (LineString $line) => $this->transformLine($line, $srid),$geometry->geometries())
        );
    }

    /**
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     */
    public function transformPolygon(Polygon $polygon, int $srid): Polygon
    {
        if ($this->projectionRegistry->get($srid)->isAligned($polygon)) {
            return $polygon;
        }
        return Polygon::of($this->transformLine($polygon->exteriorRing(), $srid));
    }

    /**
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     */
    public function transformMultiPolygon(MultiPolygon $geometry, int $srid): MultiPolygon
    {
        if ($this->projectionRegistry->get($srid)->isAligned($geometry)) {
            return $geometry;
        }
        return MultiPolygon::of(
            ...array_map(fn (Polygon $polygon) => $this->transformPolygon($polygon, $srid), $geometry->geometries())
        );
    }
}
