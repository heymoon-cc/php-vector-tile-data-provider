<?php

namespace HeyMoon\VectorTileDataProvider\Factory;

use Brick\Geo\Engine\GeometryEngine;
use Brick\Geo\Engine\GEOSEngine;

final class GEOSServiceFactory extends AbstractServiceFactory
{
    protected function createEngine(): GeometryEngine
    {
        return new GEOSEngine();
    }
}
