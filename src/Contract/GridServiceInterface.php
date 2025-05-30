<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use HeyMoon\VectorTileDataProvider\Entity\AbstractSource;
use HeyMoon\VectorTileDataProvider\Entity\Grid;

interface GridServiceInterface
{
    public function getGrid(AbstractSource $source, int $zoom, ?callable $filter = null, ?float $buffer = null): Grid;
}
