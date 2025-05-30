<?php

namespace HeyMoon\VectorTileDataProvider\Factory;

use Brick\Geo\Engine\GeometryEngine;
use HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry;
use HeyMoon\VectorTileDataProvider\Service\GridService;
use HeyMoon\VectorTileDataProvider\Service\SpatialService;
use HeyMoon\VectorTileDataProvider\Service\TileService;

abstract class AbstractServiceFactory
{
    private ?GeometryEngine $engine = null;
    private ?SpatialService $spatial = null;
    private ?SourceFactory $sourceFactory = null;
    private ?ProxySourceFactory $proxySourceFactory = null;
    private ?GeometryCollectionFactory $geometryCollectionFactory = null;
    private ?GridService $gridService = null;

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

    public function getGridService(): GridService
    {
        return $this->gridService ?? ($this->gridService = new GridService($this->getSpatialService()));
    }

    public function getSourceFactory(): SourceFactory
    {
        return $this->sourceFactory ?? ($this->sourceFactory = new SourceFactory($this->getGeometryCollectionFactory()));
    }

    public function getProxySourceFactory(): ProxySourceFactory
    {
        return $this->proxySourceFactory ?? ($this->proxySourceFactory =
            new ProxySourceFactory($this->getGeometryCollectionFactory()));
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
