<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use Brick\Geo\GeometryCollection;

interface GeometryCollectionFactoryInterface
{
    public function get(array $geometries): GeometryCollection;
}
