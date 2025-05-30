<?php

namespace HeyMoon\VectorTileDataProvider\Factory;

use HeyMoon\VectorTileDataProvider\Contract\GeometryCollectionFactoryInterface;
use HeyMoon\VectorTileDataProvider\Contract\SourceFactoryInterface;
use HeyMoon\VectorTileDataProvider\Entity\ProxySource;

class ProxySourceFactory implements SourceFactoryInterface
{
    public function __construct(private readonly GeometryCollectionFactoryInterface $geometryCollectionFactory) {}

    public function create(): ProxySource
    {
        return new ProxySource($this->geometryCollectionFactory);
    }
}
