<?php

namespace HeyMoon\MVTTools\Factory;

use Brick\Geo\Engine\GeometryEngine;
use HeyMoon\MVTTools\Registry\BasicProjectionRegistry;
use HeyMoon\MVTTools\Service\SpatialService;
use HeyMoon\MVTTools\Service\TileService;

abstract class AbstractTileServiceFactory
{
    private ?GeometryEngine $engine = null;
    private ?SpatialService $spatial = null;

    public function getTileService(...$args): TileService
    {
        return (new TileService($this->getEngine(), $this->getSpatialService(), ...$args));
    }

    public function getSpatialService(): SpatialService
    {
        return $this->spatial ?? ($this->spatial = new SpatialService(new BasicProjectionRegistry()));
    }

    protected abstract function createEngine(): GeometryEngine;

    private function getEngine(): GeometryEngine
    {
        return $this->engine ?? ($this->engine = $this->createEngine());
    }
}
