<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

class Layer
{
    private array $shapes = [];

    public function __construct(private readonly string $name, private readonly Source $source) {}

    public function add(Geometry $geometry, array $properties, int $minZoom = 0, ?int $id = null): self
    {
        $this->shapes[] = new Shape($this, $geometry, $properties, $minZoom, $id);
        return $this;
    }

    public function addCollection(
        FeatureCollection $collection, int $minZoom = 0, int $srid = WorldGeodeticProjection::SRID
    ): self
    {
        foreach ($collection->getFeatures() as $feature) {
            $properties = (array)$feature->getProperties();
            $id = $properties['id'] ?? null;
            $this->add($feature->getGeometry()->withSRID($srid), $properties, $minZoom, $id);
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

    /**
     * @return Source
     */
    public function getSource(): Source
    {
        return $this->source;
    }
}
