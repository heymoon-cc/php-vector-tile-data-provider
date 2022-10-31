<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

class ProxySource extends AbstractSource
{
    protected function createLayer(string $name): AbstractLayer
    {
        return new ProxyLayer($name, $this, $this->geometryCollectionFactory);
    }
}
