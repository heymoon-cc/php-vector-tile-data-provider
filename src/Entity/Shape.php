<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\Feature;
use Stringable;

class Shape extends AbstractSourceComponent implements Stringable
{
    private int $id;

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function __construct(
        private readonly Layer    $layer,
        private Geometry $geometry,
        private readonly array    $parameters = [],
        private readonly int      $minZoom = 0,
        ?int $id = null
    ) {
        $this->id = $this->layer->getSource()->addFeature($this, $id);
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
        return $this->geometry;
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

    public function asFeature(): Feature
    {
        return new Feature($this->getGeometry(), $this->getFeatureParameters());
    }

    public function getParameter($key): mixed
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * @return Layer
     */
    public function getLayer(): Layer
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
