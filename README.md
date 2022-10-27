# Mapbox vector tiles render library for PHP
[![PHP Version Require](http://poser.pugx.org/heymoon/mvt-tools/v)](https://packagist.org/packages/heymoon/mvt-tools)
[![PHP Version Require](http://poser.pugx.org/heymoon/mvt-tools/require/php)](https://packagist.org/packages/heymoon/mvt-tools)
[![Test](https://github.com/egbuk/mvt-tools/actions/workflows/test.yaml/badge.svg?branch=main)](https://github.com/egbuk/mvt-tools/actions/workflows/test.yaml)
[![Maintainability](https://api.codeclimate.com/v1/badges/223376b2050d8c0af9e1/maintainability)](https://codeclimate.com/github/egbuk/mvt-tools/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/223376b2050d8c0af9e1/test_coverage)](https://codeclimate.com/github/egbuk/mvt-tools/test_coverage)

Serve or write [Mapbox Vector Tile 2.1](https://github.com/mapbox/vector-tile-spec/tree/master/2.1) using PHP 8.1 
with Douglas-Peucker simplification and intersection check implementation from
[GEOS](https://libgeos.org/) via [PHP integration](https://git.osgeo.org/gitea/geos/php-geos.git) (recommended)
or [PostGIS](https://postgis.net).

You must explicitly generate protobuf classes from your project root via 
`protoc --proto_path=./vendor/heymoon/mvt-tools/proto --php_out=./vendor/heymoon/mvt-tools/proto/gen ./vendor/heymoon/mvt-tools/proto/vector_tile.proto`.
___
Additional: convert MVT tiles to SVG (debug purposes only, not designed for production).
Install [meyfa/php-svg](https://github.com/meyfa/php-svg) to use this feature.
## Spatial systems
Library includes custom implementation of SRID transformation engine in `HeyMoon\MVTTools\Service\SpatialService`
(since SRID transformation is unsupported by php-geos and has performance issues in PostGIS).
Only SRID 3857 (aka [Web Mercator](https://en.wikipedia.org/wiki/Web_Mercator_projection),
required for the tile layout calculation) and 4326
(aka [WGS 84](https://en.wikipedia.org/wiki/World_Geodetic_System#1984_version), most commonly used)
currently supported out of the box. Additional projections can be described as
subclass of `SpatialProjectionInterface` and passed in `supports` function of a new class extended
from `AbstractProjectionRegistry`. Projection class should implement conversion of point coordinates on 2D
surface from WGS 84 to the required spatial system and the reverse transformation function.
## Example usage in Symfony:
### DI configuration:
```yaml
services:
  Brick\Geo\Engine\GeometryEngine:
    class: 'Brick\Geo\Engine\GEOSEngine'
  HeyMoon\MVTTools\Factory\GeometryCollectionFactory: ~
  HeyMoon\MVTTools\Factory\SourceFactory: ~
  HeyMoon\MVTTools\Registry\AbstractProjectionRegistry:
    class: 'HeyMoon\MVTTools\Registry\BasicProjectionRegistry'
  HeyMoon\MVTTools\Registry\AbstractExportFormatRegistry:
    class: 'HeyMoon\MVTTools\Registry\ExportFormatRegistry'
  HeyMoon\MVTTools\Service\SpatialService: ~
  HeyMoon\MVTTools\Service\GridService: ~
  HeyMoon\MVTTools\Service\TileService: ~
  HeyMoon\MVTTools\Service\ExportService: ~
```
### Action:
```php
use Brick\Geo\IO\GeoJSONReader;
use Brick\Geo\Exception\GeometryException;
use HeyMoon\MVTTools\Entity\TilePosition;
use HeyMoon\MVTTools\Factory\SourceFactory;
use HeyMoon\MVTTools\Service\GridService;
use HeyMoon\MVTTools\Service\TileService;
use HeyMoon\MVTTools\Service\ExportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:export')]
class ExportCommand extends Command
{
    public function __construct(
        private readonly GeoJSONReader $geoJSONReader,
        private readonly SourceFactory $sourceFactory,
        private readonly GridService $gridService,
        private readonly TileService $tileService,
        private readonly ExportService $exportService
    )
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->addArgument('in', InputArgument::REQUIRED);
        $this->addArgument('out', InputArgument::REQUIRED);
        $this->addOption('zoom', 'z', InputOption::VALUE_OPTIONAL);
        $this->addOption('type', 't', InputOption::VALUE_OPTIONAL,
            'mvt for .mvt or svg for .svg');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $this->sourceFactory->create();
        try {
            $source->addCollection('export', $this->geoJSONReader->read(file_get_contents($input->getArgument('in'))));
            $grid = $this->gridService->getGrid($source, $input->getOption('zoom') ?? 0);
            $path = $input->getArgument('out');
            $type = $input->getOption('t') ?? 'mvt';
            $grid->iterate(fn (TilePosition $position, array $data) =>
                $this->exportService->dump(
                    $this->tileService->getTileMVT($data, $position), "$path/$position.$type")
            );
        } catch (GeometryException $e) {
            $output->writeln("Data error: {$e->getMessage()}");
            return 1;
        }
        return 0;
    }
}
```
Tested with [Symfony 6.1](https://symfony.com/releases/6.1).
## Export result
In real life scenario instead of dumping SVG files you would write data in your database of choice. For example, you
could create [MBTiles](https://github.com/mapbox/mbtiles-spec) file readable by
[tileserver-gl](https://github.com/maptiler/tileserver-gl). This is achievable
with the SQLite database containing schema from
[the latest specification description](https://github.com/mapbox/mbtiles-spec/blob/master/1.3/spec.md).
Alternatively you could store only the grid partitioning result for on-demand generation in your own vector data source
controller on PHP later (useful if faster data update is needed).
## Demo
**<https://map.heymoon.cc>**
---
Basic [Leaflet](https://leafletjs.com/)-based map example with realtime Symfony backend performance preview.
The `getTileMVT` function is called on each request without any additional static caching strategy.
Two layers are requested separately for benchmarking:
* Polygons based off 976K GeoJSON with 7 string properties.
* Lines based off 1.8M GeoJSON with no properties.

It only has 1 CPU and low RAM at its disposal so please be gentle.
