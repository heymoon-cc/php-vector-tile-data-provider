<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use HeyMoon\VectorTileDataProvider\Entity\Grid;

interface GridServiceInterface
{
    public function getGrid(SourceInterface $source, int $zoom, ?callable $filter = null, ?float $buffer = null): Grid;
}
