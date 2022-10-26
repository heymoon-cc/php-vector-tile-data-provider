<?php

namespace HeyMoon\MVTTools\Service;

use Brick\Geo\Curve;
use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\GeometryCollection;
use Brick\Geo\LineString;
use Brick\Geo\Point;
use Brick\Geo\Polygon;
use HeyMoon\MVTTools\Entity\Grid;
use HeyMoon\MVTTools\Entity\Source;
use HeyMoon\MVTTools\Entity\TilePosition;
use HeyMoon\MVTTools\Helper\GeometryHelper;
use HeyMoon\MVTTools\Spatial\WebMercatorProjection;

/**
 * Filter source shapes by minZoom and group them by common tiles on given zoom
 */
class GridService
{
    public function __construct(private readonly SpatialService $spatialService) {}

    /**
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getGrid(Source $source, int $zoom, ?callable $filter = null): Grid
    {
        $grid = [];
        $tileWidth = GeometryHelper::getTileWidth($zoom);
        foreach ($this->spatialService->check($source->getShapes(), WebMercatorProjection::SRID) as $shape) {
            if ($shape->getMinZoom() > $zoom) {
                continue;
            }
            $collection = $shape->getGeometry();
            foreach ($collection instanceof GeometryCollection ?
                         $collection->geometries() : [$collection] as $geometry) {
                if ($geometry instanceof LineString) {
                    list($minPoint, $maxPoint) = GeometryHelper::getLineBounds($geometry);
                } elseif ($geometry instanceof Point) {
                    $minPoint = $maxPoint = $geometry;
                } elseif ($geometry instanceof Polygon) {
                    list($minPoint, $maxPoint) = GeometryHelper::getBounds($geometry);
                } elseif ($geometry instanceof Curve) {
                    list($minPoint, $maxPoint) = GeometryHelper::getLineBounds(
                        LineString::of($geometry->startPoint(), $geometry->endPoint())
                    );
                } else {
                    continue;
                }
                $minColumn = $this->getColumn($minPoint, $tileWidth);
                $minRow = $this->getRow($minPoint, $tileWidth);
                $maxColumn = $this->getColumn($maxPoint, $tileWidth);
                $maxRow = $this->getRow($maxPoint, $tileWidth);
                for ($column = $minColumn; $column <= $maxColumn; $column++) {
                    for ($row = $minRow; $row <= $maxRow; $row++) {
                        $position = TilePosition::xyz($column, $row, $zoom);
                        if (is_callable($filter)) {
                            if (!$filter($position)) {
                                continue;
                            }
                        }
                        $key = $position->getKey();
                        if (!array_key_exists($key, $grid)) {
                            $grid[$key] = [];
                        }
                        $grid[$key][] = $shape;
                    }
                }
            }
        }
        return new Grid($zoom, $grid);
    }

    protected function getColumn(?Point $point, float $tileWidth): ?int
    {
        if (is_null($point)) {
            return null;
        }
        return (int)floor(($point->x() + WebMercatorProjection::EARTH_RADIUS) / $tileWidth);
    }

    protected function getRow(?Point $point, float $tileWidth): ?int
    {
        if (is_null($point)) {
            return null;
        }
        return (int)floor(($point->y() + WebMercatorProjection::EARTH_RADIUS) / $tileWidth);
    }
}
