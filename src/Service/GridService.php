<?php

namespace HeyMoon\VectorTileDataProvider\Service;

use Brick\Geo\Engine\GeometryEngine;
use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\GeometryEngineException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\GeometryCollection;
use Brick\Geo\Point;
use HeyMoon\VectorTileDataProvider\Contract\SourceInterface;
use HeyMoon\VectorTileDataProvider\Contract\SpatialServiceInterface;
use HeyMoon\VectorTileDataProvider\Entity\Grid;
use HeyMoon\VectorTileDataProvider\Entity\TilePosition;
use HeyMoon\VectorTileDataProvider\Helper\GeometryHelper;
use HeyMoon\VectorTileDataProvider\Contract\GridServiceInterface;
use HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection;

/**
 * Filter source features by minZoom and group them by common tiles on given zoom
 */
class GridService implements GridServiceInterface
{
    public function __construct(
        private readonly SpatialServiceInterface $spatialService,
        private readonly ?GeometryEngine $geometryEngine = null
    ) {}

    /**
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     * @throws GeometryEngineException
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getGrid(SourceInterface $source, int $zoom, ?callable $filter = null, ?float $buffer = null): Grid
    {
        $grid = [];
        $tileWidth = GeometryHelper::getTileWidth($zoom);
        foreach ($this->spatialService->check($source->getFeatures(), WebMercatorProjection::SRID) as $feature) {
            if ($feature->getMinZoom() > $zoom) {
                continue;
            }
            $collection = $feature->getGeometry();
            foreach ($collection instanceof GeometryCollection ? $collection->geometries() : [$collection] as $geometry) {
                $bounds = ($buffer ? $this->geometryEngine->buffer($geometry, $buffer * $tileWidth) : $geometry)
                    ->getBoundingBox();
                $westColumn = $this->getColumn($bounds->getSouthWest(), $tileWidth);
                $westRow = $this->getRow($bounds->getSouthWest(), $tileWidth);
                $eastColumn = $this->getColumn($bounds->getNorthEast(), $tileWidth);
                $eastRow = $this->getRow($bounds->getNorthEast(), $tileWidth);
                $minColumn = min($westColumn, $eastColumn);
                $maxColumn = max($westColumn, $eastColumn);
                $minRow = min($westRow, $eastRow);
                $maxRow = max($westRow, $eastRow);
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
                        $grid[$key][] = $feature;
                    }
                }
            }
        }
        return new Grid($zoom, $grid);
    }

    protected function getColumn(Point $point, float $tileWidth): ?int
    {
        return (int)floor(($point->x() + WebMercatorProjection::EARTH_RADIUS) / $tileWidth);
    }

    protected function getRow(Point $point, float $tileWidth): ?int
    {
        return (int)floor(($point->y() + WebMercatorProjection::EARTH_RADIUS) / $tileWidth);
    }
}
