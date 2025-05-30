<?php

namespace HeyMoon\VectorTileDataProvider\Service;

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
use HeyMoon\VectorTileDataProvider\Entity\AbstractSourceComponent;
use HeyMoon\VectorTileDataProvider\Entity\Feature;
use HeyMoon\VectorTileDataProvider\Exception\SpatialSystemDecodeException;
use HeyMoon\VectorTileDataProvider\Exception\SpatialSystemEncodeException;
use HeyMoon\VectorTileDataProvider\Contract\SpatialServiceInterface;
use HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry;
use HeyMoon\VectorTileDataProvider\Spatial\WorldGeodeticProjection;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class SpatialService extends AbstractSourceComponent implements SpatialServiceInterface
{
    public function __construct(private readonly AbstractProjectionRegistry $projectionRegistry) {}

    /**
     * @param Feature[] $features
     * @param int $srid
     * @return Feature[]
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     */
    public function check(array $features, int $srid): array
    {
        return array_map(fn(Feature $feature) =>
        $feature->getGeometry()->SRID() === $srid ? $feature : $feature->setGeometry(
            $this->transform($feature->getGeometry(), $srid)),
            $features
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
    public function transformMultiPoint(MultiPoint $geometry, int $srid, ?int $parentSRID = null): MultiPoint
    {
        if ($this->projectionRegistry->get($srid)->isAligned($geometry)) {
            return $geometry;
        }
        return MultiPoint::of(
            ...array_map(fn(Point $point) =>
            $this->transformPoint($point->withSRID($parentSRID ?? $geometry->SRID()),
                $srid),
                $geometry->geometries())
        )->withSRID($srid);
    }

    /**
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public function transformLine(LineString $line, int $srid, ?int $parentSRID = null): LineString
    {
        if ($this->projectionRegistry->get($srid)->isAligned($line)) {
            return $line;
        }
        return LineString::of(...array_map(fn (Point $point) => $this->transformPoint(
            $point->withSRID($parentSRID ?? $line->SRID()), $srid),
            $line->points()))->withSRID($srid);
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws InvalidGeometryException
     */
    public function transformMultiLine(MultiLineString $geometry, int $srid, ?int $parentSRID = null): MultiLineString
    {
        if ($this->projectionRegistry->get($srid)->isAligned($geometry)) {
            return $geometry;
        }
        return MultiLineString::of(
            ...array_map(fn (LineString $line) => $this->transformLine($line, $srid,
                $parentSRID ?? $geometry->SRID()),
                $geometry->geometries())
        )->withSRID($srid);
    }

    /**
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     */
    public function transformPolygon(Polygon $polygon, int $srid, ?int $parentSRID = null): Polygon
    {
        if ($this->projectionRegistry->get($srid)->isAligned($polygon)) {
            return $polygon;
        }
        return Polygon::of($this->transformLine($polygon->exteriorRing(), $srid,
            $parentSRID ?? $polygon->SRID()))
            ->withSRID($srid);
    }

    /**
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     */
    public function transformMultiPolygon(MultiPolygon $geometry, int $srid, ?int $parentSRID = null): MultiPolygon
    {
        if ($this->projectionRegistry->get($srid)->isAligned($geometry)) {
            return $geometry;
        }
        return MultiPolygon::of(
            ...array_map(fn (Polygon $polygon) => $this->transformPolygon($polygon, $srid,
                $parentSRID ?? $geometry->SRID()),
                $geometry->geometries()
            )
        )->withSRID($srid);
    }
}
