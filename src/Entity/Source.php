<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Countable;
use HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory;
use HeyMoon\VectorTileDataProvider\Spatial\WorldGeodeticProjection;

class Source extends AbstractSource
{
    protected function createLayer(string $name): AbstractLayer
    {
        return new Layer($name, $this, $this->geometryCollectionFactory);
    }
}
