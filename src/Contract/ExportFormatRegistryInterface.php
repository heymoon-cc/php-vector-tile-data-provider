<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

use HeyMoon\VectorTileDataProvider\Export\ExportFormatInterface;

interface ExportFormatRegistryInterface
{
    public function byPath(string $path): ExportFormatInterface;
    public function get(string $ext): ExportFormatInterface;
}