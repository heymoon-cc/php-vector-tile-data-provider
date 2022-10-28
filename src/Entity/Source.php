<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Countable;
use HeyMoon\MVTTools\Factory\GeometryCollectionFactory;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

class Source implements Countable
{
    /** @var Layer[] */
    private array $layers = [];

    public function __construct(private readonly GeometryCollectionFactory $geometryCollectionFactory) {}

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

    public function getLayer(string $name): Layer
    {
        return $this->layers[$name] ?? ($this->layers[$name] = new Layer($name, $this, $this->geometryCollectionFactory));
    }

    /**
     * @return Shape[]
     */
    public function getShapes(): array
    {
        return array_merge(...array_map(fn(Layer $layer) => $layer->getShapes(), array_values($this->layers)));
    }

    public function count(): int
    {
        return array_reduce($this->layers, fn(int $c, Layer $layer) => $c + $layer->count(), 0);
    }
}
