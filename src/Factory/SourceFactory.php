<?php

namespace HeyMoon\VectorTileDataProvider\Factory;

use HeyMoon\VectorTileDataProvider\Entity\Source;

class SourceFactory
{
    public function __construct(private readonly GeometryCollectionFactory $geometryCollectionFactory) {}

    public function create(): Source
    {
        return new Source($this->geometryCollectionFactory);
    }
}