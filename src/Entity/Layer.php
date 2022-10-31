<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

use ArrayAccess;
use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\GeometryCollection;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Countable;
use HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory;
use HeyMoon\VectorTileDataProvider\Spatial\WorldGeodeticProjection;

class Layer extends AbstractSourceComponent implements ArrayAccess, Countable
{
    private array $features = [];

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
        new Feature($this, $geometry, $properties, $minZoom, $id);
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
     * @return Feature[]
     */
    public function getFeatures(): array
    {
        return $this->features;
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
        return new FeatureCollection(...array_map(fn(Feature $shape) => $shape->asGeoJSONFeature(), $this->features));
    }

    public function count(): int
    {
        return count($this->features);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->features);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->features[$offset];
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof Feature) {
            return;
        }
        $this->add($value->getGeometry(), $value->getParameters(), $value->getMinZoom(), $offset);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->features[$offset]);
    }

    /**
     * @param Feature $feature
     * @param int|null $id
     * @return int
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function addFeature(Feature $feature, ?int $id = null): int
    {
        $exists = $id && array_key_exists($id, $this->features);
        if ($exists) {
            $target = $this->features[$id]->getGeometry();
            $add = $feature->getGeometry();
            /** @var Geometry[] $collection */
            $collection = array_merge(
                $target instanceof GeometryCollection ? $target->geometries() : [$target],
                $add instanceof GeometryCollection ? $add->geometries() : [$add]
            );
            $this->features[$id] = $feature->setGeometry($this->geometryCollectionFactory->get($collection));
            return $id;
        }
        $id || empty($this->features) ?
            ($this->features[$id ?? 1] = $feature) :
            ($this->features[] = $feature);
        return $id ?? array_key_last($this->features);
    }
}
