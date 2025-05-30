<?php

namespace HeyMoon\VectorTileDataProvider\Factory;

use HeyMoon\VectorTileDataProvider\Contract\GeometryCollectionFactoryInterface;
use HeyMoon\VectorTileDataProvider\Contract\SourceFactoryInterface;
use HeyMoon\VectorTileDataProvider\Entity\ProxySource;
use HeyMoon\VectorTileDataProvider\Entity\Source;

class SourceFactory implements SourceFactoryInterface
{
    public function __construct(private readonly GeometryCollectionFactoryInterface $geometryCollectionFactory) {}

    public function create(): Source
    {
        return new Source($this->geometryCollectionFactory);
    }

    /**
     * @deprecated use ProxySourceFactory instead
     * @return ProxySource
     */
    public function createProxy(): ProxySource
    {
        return new ProxySource($this->geometryCollectionFactory);
    }
}
