<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Geometry;

abstract class AbstractSourceComponent
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function addFeature(Shape $feature, ?int $id = null): int
    {
        return 0;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setGeometry(Geometry $geometry): self
    {
        return $this;
    }
}
