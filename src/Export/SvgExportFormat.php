<?php

namespace HeyMoon\VectorTileDataProvider\Export;

use ErrorException;
use HeyMoon\VectorTileDataProvider\Contract\TileServiceInterface;
use HeyMoon\VectorTileDataProvider\Service\TileService;
use SVG\Nodes\Shapes\SVGCircle;
use SVG\Nodes\Shapes\SVGPath;
use SVG\Nodes\Shapes\SVGRect;
use SVG\SVG;
use Vector_tile\Tile;

class SvgExportFormat extends AbstractExportFormat
{
    /**
     * @throws ErrorException
     */
    public function export(TileServiceInterface $service, Tile $tile, callable|string|null $color = null): object|string
    {
        $extent = $service->getExtent($tile);
        $image = new SVG($extent, $extent);
        $doc = $image->getDocument();
        $doc->addChild((new SVGRect(0, 0, $extent, $extent))->setStyle('fill', '#fff'));
        foreach ($tile->getLayers() as $layer) {
            /** @var Tile\Layer $layer */
            foreach ($layer->getFeatures() as $feature) {
                /** @var Tile\Feature $feature */
                $values = $service->getValues($layer, $feature);
                $stroke = $values['_debug_color'] ?? '#0000ff1a';
                if (is_string($color)) {
                    $stroke = $values[$color] ?? $stroke;
                } elseif (is_callable($color)) {
                    $stroke = $color($values);
                }
                if ($feature->getType() === Tile\GeomType::POINT) {
                    $x = $service->decodeValue($feature->getGeometry()->offsetGet(1));
                    $y = $service->decodeValue($feature->getGeometry()->offsetGet(2));
                    $point = new SVGCircle($x, $y, $extent / 500);
                    $point->setStyle('fill', $stroke);
                    $doc->addChild($point);
                    continue;
                }
                $path = '';
                $command = null;
                $n = null;
                $count = 0;
                for ($i = 0; $i <= $feature->getGeometry()->count(); $i++) {
                    if (!$feature->getGeometry()->offsetExists($i)) {
                        break;
                    }
                    $item = $feature->getGeometry()->offsetGet($i);
                    if (!$command) {
                        list($commandCode, $n) = $service->decodeCommand(
                            $item
                        );
                        $command = match ($commandCode) {
                            TileService::MOVE_TO => 'M',
                            TileService::LINE_TO => 'l',
                            TileService::CLOSE_PATH => 'z'
                        };
                        $count = 0;
                        continue;
                    }
                    $i++;
                    if (!$feature->getGeometry()->offsetExists($i)) {
                        break;
                    }
                    $x = $service->decodeValue($item);
                    $y = $service->decodeValue($feature->getGeometry()->offsetGet($i));
                    $path .= " $command $x,$y";
                    $count++;
                    if ($count >= $n) {
                        $command = null;
                    }
                }
                $line = new SVGPath(substr($path, 1));
                $line->setStyle('stroke', $stroke);
                $line->setStyle('stroke-width', $extent / 500);
                $line->setStyle('fill', $feature->getType() === Tile\GeomType::LINESTRING ? 'none' : $stroke);
                $doc->addChild($line);
            }
        }
        return $image;
    }

    public function require(): array
    {
        return ['meyfa/php-svg'];
    }

    protected static function defaultSupports(): array
    {
        return ['svg'];
    }

    protected function getDependencyClass(): string
    {
        return SVG::class;
    }
}
