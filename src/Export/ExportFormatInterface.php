<?php

namespace HeyMoon\MVTTools\Export;

use HeyMoon\MVTTools\Service\TileService;
use Vector_tile\Tile;

interface ExportFormatInterface
{
    public static function get(array $supports = []): static;

    public function supports(): array;

    public function export(TileService $service, Tile $tile, string|callable|null $color = null): object|string;

    public function isAvailable(): bool;

    public function require(): array;
}
