<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use HeyMoon\VectorTileDataProvider\Contract\SourceInterface;
use HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory;
use HeyMoon\VectorTileDataProvider\Spatial\WorldGeodeticProjection;

abstract class AbstractSource implements SourceInterface
{
    /** @var AbstractLayer[] */
    private array $layers = [];

    public function __construct(protected readonly GeometryCollectionFactory $geometryCollectionFactory) {}

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function add(string $name, Geometry $geometry, array $properties = [], int $minZoom = 0, ?int $id = null): self
    {
        $this->getLayer($name)->add($geometry, $properties, $minZoom, $id);
        return $this;
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function addCollection(string $name, FeatureCollection $collection, int $minZoom = 0, int $srid = WorldGeodeticProjection::SRID): self
    {
        $this->getLayer($name)->addCollection($collection, $minZoom, $srid);
        return $this;
    }

    public function getLayer(string $name): AbstractLayer
    {
        return $this->layers[$name] ?? ($this->layers[$name] = $this->createLayer($name));
    }

    /**
     * @return Feature[]
     */
    public function getFeatures(): array
    {
        return array_merge(...array_map(fn(AbstractLayer $layer) => $layer->getFeatures(), array_values($this->layers)));
    }

    public function count(): int
    {
        return array_reduce($this->layers, fn(int $c, AbstractLayer $layer) => $c + $layer->count(), 0);
    }

    protected abstract function createLayer(string $name): AbstractLayer;
}
