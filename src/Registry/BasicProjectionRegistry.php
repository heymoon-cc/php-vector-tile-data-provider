<?php

namespace HeyMoon\VectorTileDataProvider\Registry;

use HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection;
use HeyMoon\VectorTileDataProvider\Spatial\WorldGeodeticProjection;

class BasicProjectionRegistry extends AbstractProjectionRegistry
{
    /**
     * Additional projections only, 3857 and 4326 always included by default
     */
    protected function supports(): array
    {
        return [];
    }
}
