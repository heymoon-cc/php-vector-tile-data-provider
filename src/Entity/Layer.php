<?php

namespace HeyMoon\MVTTools\Entity;

use ArrayAccess;
use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\GeometryCollection;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Countable;
use HeyMoon\MVTTools\Factory\GeometryCollectionFactory;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

class Layer extends AbstractSourceComponent implements ArrayAccess, Countable
{
    private array $shapes = [];

    public function __construct(
        private readonly string $name,
        private readonly Source $source,
        private readonly GeometryCollectionFactory $geometryCollectionFactory
    ) {}

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function add(Geometry $geometry, array $properties = [], int $minZoom = 0, ?int $id = null): self
    {
        new Shape($this, $geometry, $properties, $minZoom, $id);
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

    /**
     * @param Shape $feature
     * @param int|null $id
     * @return int
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function addFeature(Shape $feature, ?int $id = null): int
    {
        $exists = $id && array_key_exists($id, $this->shapes);
        if ($exists) {
            $target = $this->shapes[$id]->getGeometry();
            $add = $feature->getGeometry();
            /** @var Geometry[] $collection */
            $collection = array_merge(
                $target instanceof GeometryCollection ? $target->geometries() : [$target],
                $add instanceof GeometryCollection ? $add->geometries() : [$add]
            );
            $this->shapes[$id] = $feature->setGeometry($this->geometryCollectionFactory->get($collection));
            return $id;
        }
        $id || empty($this->shapes) ?
            ($this->shapes[$id ?? 1] = $feature) :
            ($this->shapes[] = $feature);
        return $id ?? array_key_last($this->shapes);
    }
}
