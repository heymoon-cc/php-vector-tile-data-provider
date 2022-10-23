<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

class Layer
{
    private array $shapes = [];

    public function __construct(private readonly string $name) {}

    public function add(Geometry $geometry, array $properties): self
    {
        $this->shapes[] = new Shape($this, $geometry, $properties);
        return $this;
    }

    public function addCollection(FeatureCollection $collection, int $srid = WorldGeodeticProjection::SRID): self
    {
        foreach ($collection->getFeatures() as $feature) {
            $this->add($feature->getGeometry()->withSRID($srid), (array)$feature->getProperties());
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Shape[]
     */
    public function getShapes(): array
    {
        return $this->shapes;
    }
}
