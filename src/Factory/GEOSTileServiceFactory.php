<?php

namespace HeyMoon\MVTTools\Factory;

use Brick\Geo\Engine\GeometryEngine;
use Brick\Geo\Engine\GEOSEngine;

final class GEOSTileServiceFactory extends AbstractTileServiceFactory
{
    protected function createEngine(): GeometryEngine
    {
        return new GEOSEngine();
    }
}
