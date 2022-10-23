<?php

namespace HeyMoon\MVTTools\Registry;

use HeyMoon\MVTTools\Spatial\WebMercatorProjection;
use HeyMoon\MVTTools\Spatial\WorldGeodeticProjection;

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
