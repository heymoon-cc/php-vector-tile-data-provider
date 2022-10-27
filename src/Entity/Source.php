<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\GeometryCollection;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Countable;
use HeyMoon\MVTTools\Factory\GeometryCollectionFactory;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

class Source extends AbstractSourceComponent implements Countable
{
    /** @var Shape[] */
    private array $features = [];

    /** @var Layer[] */
    private array $layers = [];

    private ?int $srid = null;

    public function __construct(private readonly GeometryCollectionFactory $geometryCollectionFactory) {}

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
    public function add(string $name, Geometry $geometry, array $properties = [], int $minZoom = 0, ?int $id = null): self
    {
        $this->getLayer($name)->add($geometry, $properties, $minZoom, $id);
        return $this;
    }

    /**
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     */
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

    public function count(): int
    {
        return count($this->features);
    }

    /**
     * @param Shape $feature
     * @param int|null $id
     * @return int
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function addFeature(Shape $feature, ?int $id = null): int
    {
        if ($this->srid && $feature->getGeometry()->SRID() !== $this->srid) {
            throw CoordinateSystemException::sridMix($feature->getGeometry()->SRID(), $this->srid);
        }
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
