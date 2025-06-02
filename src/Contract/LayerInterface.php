<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use HeyMoon\VectorTileDataProvider\Spatial\WorldGeodeticProjection;
use ArrayAccess;
use Countable;

interface LayerInterface extends ArrayAccess, Countable
{
    public function add(Geometry $geometry, array $properties = [], int $minZoom = 0, ?int $id = null): self;
    public function getName(): string;
    public function getFeatures(): array;
    public function getSource(): SourceInterface;
    public function getFeatureCollection(): FeatureCollection;
    public function addCollection(FeatureCollection $collection,
                                  int $minZoom = 0, int $srid = WorldGeodeticProjection::SRID);
}
