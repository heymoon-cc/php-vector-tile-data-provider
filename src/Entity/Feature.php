<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\Feature as GeoJSONFeature;
use Brick\Geo\Proxy\ProxyInterface;
use HeyMoon\VectorTileDataProvider\Contract\LayerInterface;
use HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory;
use Stringable;

class Feature extends AbstractSourceComponent implements Stringable
{
    private int $id;

    private ?Geometry $geometry;
    private array $collection = [];

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function __construct(
        private readonly GeometryCollectionFactory $geometryCollectionFactory,
        private readonly AbstractLayer    $layer,
        Geometry $geometry,
        private readonly array    $parameters = [],
        private readonly int      $minZoom = 0,
        ?int $id = null
    ) {
        $this->geometry = $geometry;
        $this->id = $this->layer->addFeature($this, $id);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMinZoom(): int
    {
        return $this->minZoom;
    }

    /**
     * @return Geometry
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function getGeometry(): Geometry
    {
        return $this->geometry ? ($this->geometry instanceof ProxyInterface ? clone $this->geometry : $this->geometry) :
            $this->geometry = $this->geometryCollectionFactory->get($this->collection);
    }

    protected function setGeometry(?Geometry $geometry): self
    {
        $this->geometry = $geometry;
        return $this;
    }

    protected function setCollection(array $collection): self
    {
        $this->collection = $collection;
        return $this;
    }

    protected function getCollection(): array
    {
        return $this->collection;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return array_diff_key($this->parameters, ['id' => $this->id]);
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function asGeoJSONFeature(): GeoJSONFeature
    {
        return new GeoJSONFeature($this->getGeometry(), $this->getFeatureParameters());
    }

    public function getParameter($key): mixed
    {
        return $this->parameters[$key] ?? null;
    }

    public function getLayer(): LayerInterface
    {
        return $this->layer;
    }

    public function __toString(): string
    {
        return "{$this->layer->getName()}$this->id";
    }

    protected function getFeatureParameters(): object
    {
        return (object)array_merge($this->parameters, ['id' => $this->id]);
    }
}
