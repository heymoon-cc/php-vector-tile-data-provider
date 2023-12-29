<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

use Brick\Geo\Geometry;

abstract class AbstractSourceComponent
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function addFeature(Feature $feature, ?int $id = null): int
    {
        return 0;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setGeometry(?Geometry $geometry): self
    {
        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setCollection(array $collection): self
    {
        return $this;
    }

    protected function getCollection(): array
    {
        return [];
    }
}
