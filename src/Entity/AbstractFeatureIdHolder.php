<?php

namespace HeyMoon\MVTTools\Entity;

abstract class AbstractFeatureIdHolder
{
    protected function addFeature(Shape $feature, ?int $id = null): int
    {
        return 0;
    }
}
