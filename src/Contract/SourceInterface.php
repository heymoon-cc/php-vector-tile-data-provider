<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Countable;
use HeyMoon\VectorTileDataProvider\Spatial\WorldGeodeticProjection;

interface SourceInterface extends Countable
{
    public function add(string $name, Geometry $geometry, array $properties = [], int $minZoom = 0, ?int $id = null): self;
    public function addCollection(string $name, FeatureCollection $collection, int $minZoom = 0, int $srid = WorldGeodeticProjection::SRID): self;
    public function getLayer(string $name): LayerInterface;
    public function getFeatures(): array;
}
