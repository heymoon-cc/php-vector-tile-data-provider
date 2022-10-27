<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

class Source extends AbstractFeatureIdHolder
{
    private array $features = [];

    private array $layers = [];

    public function add(string $name, Geometry $geometry, array $properties, int $minZoom = 0, ?int $id = null): self
    {
        $this->getLayer($name)->add($geometry, $properties, $minZoom);
        return $this;
    }

    public function addCollection(string $name, FeatureCollection $collection, int $minZoom = 0, int $srid = WorldGeodeticProjection::SRID): self
    {
        $this->getLayer($name)->addCollection($collection, $minZoom, $srid);
        return $this;
    }

    public function getLayer(string $name): Layer
    {
        return $this->layers[$name] ?? ($this->layers[$name] = new Layer($name, $this));
    }

    /**
     * @return Shape[]
     */
    public function getShapes(): array
    {
        return $this->features;
    }

    protected function addFeature(Shape $feature, ?int $id = null): int
    {
        $id ? $this->features[$id] = $feature : $this->features[] = $feature;
        return array_key_last($this->features);
    }
}
