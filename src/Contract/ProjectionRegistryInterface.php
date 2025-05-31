<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use HeyMoon\VectorTileDataProvider\Spatial\SpatialProjectionInterface;

interface ProjectionRegistryInterface
{
    public function get(int $srid): ?SpatialProjectionInterface;
}
