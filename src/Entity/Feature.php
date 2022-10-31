<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\Feature as GeoJSONFeature;
use Brick\Geo\Proxy\ProxyInterface;
use Stringable;

class Feature extends AbstractSourceComponent implements Stringable
{
    private int $id;

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function __construct(
        private readonly AbstractLayer    $layer,
        private Geometry $geometry,
        private readonly array    $parameters = [],
        private readonly int      $minZoom = 0,
        ?int $id = null
    ) {
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
     */
    public function getGeometry(): Geometry
    {
        return $this->geometry instanceof ProxyInterface ? clone $this->geometry : $this->geometry;
    }

    protected function setGeometry(Geometry $geometry): self
    {
        $this->geometry = $geometry;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return array_diff_key($this->parameters, ['id' => $this->id]);
    }

    public function asGeoJSONFeature(): GeoJSONFeature
    {
        return new GeoJSONFeature($this->getGeometry(), $this->getFeatureParameters());
    }

    public function getParameter($key): mixed
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * @return AbstractLayer
     */
    public function getLayer(): AbstractLayer
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
