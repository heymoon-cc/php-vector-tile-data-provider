<?php

namespace HeyMoon\MVTTools\Factory;

use Brick\Geo\Engine\GeometryEngine;
use HeyMoon\MVTTools\Registry\BasicProjectionRegistry;
use HeyMoon\MVTTools\Service\SpatialService;
use HeyMoon\MVTTools\Service\TileService;

abstract class AbstractServiceFactory
{
    private ?GeometryEngine $engine = null;
    private ?SpatialService $spatial = null;
    private ?SourceFactory $sourceFactory = null;
    private ?GeometryCollectionFactory $geometryCollectionFactory = null;

    public function getTileService(...$args): TileService
    {
        return new TileService(
            $this->getEngine(),
            $this->getSourceFactory(),
            $this->getGeometryCollectionFactory(),
            ...$args);
    }

    public function getSpatialService(): SpatialService
    {
        return $this->spatial ?? ($this->spatial = new SpatialService(new BasicProjectionRegistry()));
    }

    public function getSourceFactory(): SourceFactory
    {
        return $this->sourceFactory ?? ($this->sourceFactory = new SourceFactory($this->getGeometryCollectionFactory()));
    }

    protected abstract function createEngine(): GeometryEngine;

    private function getEngine(): GeometryEngine
    {
        return $this->engine ?? ($this->engine = $this->createEngine());
    }

    private function getGeometryCollectionFactory(): GeometryCollectionFactory
    {
        return $this->geometryCollectionFactory ?? ($this->geometryCollectionFactory = new GeometryCollectionFactory());
    }
}
