<?php

namespace HeyMoon\MVTTools\Entity;

use ArrayAccess;
use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Countable;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

class Layer implements ArrayAccess, Countable
{
    private array $shapes = [];

    public function __construct(private readonly string $name, private readonly Source $source) {}

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function add(Geometry $geometry, array $properties = [], int $minZoom = 0, ?int $id = null): self
    {
        $shape = new Shape($this, $geometry, $properties, $minZoom, $id);
        $this->shapes[$shape->getId()] = $shape;
        return $this;
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function addCollection(
        FeatureCollection $collection, int $minZoom = 0, int $srid = WorldGeodeticProjection::SRID
    ): self
    {
        foreach ($collection->getFeatures() as $feature) {
            $properties = (array)$feature->getProperties();
            $this->add($feature->getGeometry()->withSRID($srid), $properties, $minZoom, $properties['id'] ?? null);
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

    public function getFeatureCollection(): FeatureCollection
    {
        return new FeatureCollection(...array_map(fn(Shape $shape) => $shape->asFeature(), $this->shapes));
    }

    public function count(): int
    {
        return count($this->shapes);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->shapes);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->shapes[$offset];
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof Shape) {
            return;
        }
        $this->add($value->getGeometry(), $value->getParameters(), $value->getMinZoom(), $offset);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->shapes[$offset]);
    }
}
