<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

class Source
{
    private array $layers = [];

    public function add(string $name, Geometry $geometry, array $properties): self
    {
        $this->getLayer($name)->add($geometry, $properties);
        return $this;
    }

    public function addCollection(string $name, FeatureCollection $collection, int $srid = WorldGeodeticProjection::SRID): self
    {
        $this->getLayer($name)->addCollection($collection, $srid);
        return $this;
    }

    public function getLayer(string $name): Layer
    {
        return $this->layers[$name] ?? ($this->layers[$name] = new Layer($name));
    }

    /**
     * @return Shape[]
     */
    public function getShapes(): array
    {
        return array_merge(...array_map(fn(Layer $layer) => $layer->getShapes(), array_values($this->layers)));
    }
}
