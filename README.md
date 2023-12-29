# Mapbox vector tiles library for PHP
[![Version](http://poser.pugx.org/heymoon/vector-tile-data-provider/v)](https://packagist.org/packages/heymoon/vector-tile-data-provider)
[![PHP Version Require](http://poser.pugx.org/heymoon/vector-tile-data-provider/require/php)](https://packagist.org/packages/heymoon/vector-tile-data-provider)
[![Test](https://github.com/heymoon-cc/php-vector-tile-data-provider/actions/workflows/test.yaml/badge.svg)](https://github.com/heymoon-cc/php-vector-tile-data-provider/actions/workflows/test.yaml)
[![Maintainability](https://api.codeclimate.com/v1/badges/14e051ef07dc37acca40/maintainability)](https://codeclimate.com/github/heymoon-cc/php-vector-tile-data-provider/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/14e051ef07dc37acca40/test_coverage)](https://codeclimate.com/github/heymoon-cc/php-vector-tile-data-provider/test_coverage)

Symfony + Redis demo: **<https://map.heymoon.cc>**
---
![Screenshot](https://repository-images.githubusercontent.com/556105367/1ae4eed2-6718-45ea-909e-16aad3ef7dc9)

Basic [Leaflet](https://leafletjs.com/)-based map example with realtime Symfony backend performance preview.
When "Use cache" checkbox is not active, `getTileMVT` function is called on each request without any additional static
caching strategy. With "Use cache" option, backend renders each tile only once and stores results in Redis.
Two layers are requested separately for benchmarking:
* Polygons based off 976K GeoJSON with 7 string properties.
* Lines based off 2.8M GeoJSON with 139 optional properties.

It only has 1 CPU and low RAM at its disposal so please be gentle.
## Summary

Convert [OpenGIS](https://www.ogc.org/standards/sfa) data loaded by [brick/geo](https://github.com/brick/geo) directly
to [Mapbox Vector Tile 2.1](https://github.com/mapbox/vector-tile-spec/tree/master/2.1) format. Focused on
frequent source data changes delivery with the lowest latency possible. Process data fast with [GEOS](https://libgeos.org) C/C++ library via
[PHP integration](https://git.osgeo.org/gitea/geos/php-geos.git) with custom update trigger to fit your needs.
Perform SRID transformation and Douglas-Peucker simplification faster than ever.
___
Additional: convert MVT tiles to SVG (debug purposes only, not designed for production).
Install [meyfa/php-svg](https://github.com/meyfa/php-svg) to use this feature.

## Motivation
If you want to display current weather conditions or some moving objects on your map, it's good to be able to generate
your tileset directly on receiving updates from data provider, for example when reading GeoJSON from HTTP API response.
The bottleneck is usually encountered with the following:
* Necessity to use intermediate storage compatible with some 3rd-party utility
for preparing MVT
* Generating tiles for large zoom levels
* Sharing update between server nodes, especially if it's stored in single
[MBTiles](https://github.com/mapbox/mbtiles-spec) file.
Of course, you can [generate tiles from your PostGIS database](https://github.com/mapbox/postgis-vt-util) which will allow
for frequent source data update, but you'll probably have a hard time battling performance drops after each index update.
So, with standard toolset you'll be forced to choose between low update latency and low response time.
But it shouldn't be that way. This is where this library could help.

## How is it faster
Most likely your data doesn't always change in every tile. And even if it does, if you'll look into request distribution
by zoom, you'll probably notice that most of generated tiles smaller than, for example, zoom 18, are rarely requested
between data changes but take the most of update's time, so you may want to prioritize some tiles more than others.
With enough flexibility it is possible to invalidate parts of previous result and process in advance only frequently 
requested scales, leaving the rest to on-demand processing and updating MVT cache only on HTTP-request.
With this library you'll be able to implement tight integration with the specific update scenario
and minimize redundant calculations with custom update pipeline. Also, since [GEOS](https://libgeos.org)
functions can be called directly, it's much easier to scale.

## Installation
`composer require heymoon/vector-tile-data-provider`

You must explicitly generate protobuf classes from your project root:

`protoc --proto_path=./vendor/heymoon/vector-tile-data-provider/proto --php_out=./vendor/heymoon/vector-tile-data-provider/proto/gen ./vendor/heymoon/vector-tile-data-provider/proto/vector_tile.proto`

Install `php-geos` and `php-protobuf` extensions for best performance. Example Dockerfile for
[Alpine 3.16 with PHP 8.1](https://hub.docker.com/layers/library/php/8.1-alpine3.16/images/sha256-298daac152760e2510ff283b0785c8feef72a2b134b27af918a80e40f26c1bb8):
```Dockerfile
FROM php:8.1-alpine3.16
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    geos-dev \
    git
RUN apk add --no-cache protoc geos
RUN pecl install protobuf \
    && docker-php-ext-enable protobuf \
	&& git clone https://git.osgeo.org/gitea/geos/php-geos.git /usr/src/php/ext/geos && cd /usr/src/php/ext/geos && \
	./autogen.sh && ./configure && make && \
	echo "extension=/usr/src/php/ext/geos/modules/geos.so" > /usr/local/etc/php/conf.d/docker-php-ext-geos.ini
RUN apk del -f .build-deps && rm -rf /tmp/* /var/cache/apk/*
# run "composer install" and then...
RUN protoc --proto_path=./vendor/heymoon/vector-tile-data-provider/proto --php_out=./vendor/heymoon/vector-tile-data-provider/proto/gen ./vendor/heymoon/vector-tile-data-provider/proto/vector_tile.proto
```

## Provides
* `HeyMoon\VectorTileDataProvider\Entity\Source` and `Entity\SourceProxy` (storing geometries as WKB until evaluated) instances initialized by `HeyMoon\VectorTileDataProvider\Factory\SourceFactory` for easy data load
from `Brick\Geo\IO\GeoJSON\FeatureCollection` or manually populated `Brick\Geo\Geometry` objects.
* `HeyMoon\VectorTileDataProvider\Service\SpatialService` for cheap spatial system transformation.
* `HeyMoon\VectorTileDataProvider\Service\GridService` and resulting `HeyMoon\VectorTileDataProvider\Entity\Grid` instance for filtering geometries
visible only in particular tile and assigned to particular thread (threading can be achieved through providing filter
callback function in `GridService::getGrid` to skip tiles based on position). This operation is the most demanding of
RAM, but could be completed much faster than full result generation.
* `HeyMoon\VectorTileDataProvider\Service\TileService` for
[Mapbox Vector Tile 2.1](https://github.com/mapbox/vector-tile-spec/tree/master/2.1) generation, presumably in
`Grid::iterate` callback or HTTP request, reading required geometries from pre-saved `GridService` groups.
Geometry simplification is performed only on `TileService::getTileMVT`.
* `HeyMoon\VectorTileDataProvider\Service\ExportService` for basic export to `.mvt`, to serve tileset as static files via NGINX,
or `.svg` for result preview.
* `HeyMoon\VectorTileDataProvider\Factory\TileFactory` for parsing and merging ready vector tiles.

## Spatial systems
By default, grid is expected to be aligned with the
[Web Mercator projection](https://en.wikipedia.org/wiki/Web_Mercator_projection), which is most likely different from
your original data source spatial reference system. In order to process inputs with arbitrary SRID, library includes
custom implementation of spatial transformation engine in `HeyMoon\VectorTileDataProvider\Service\SpatialService` (since SRID
transformation is unsupported by php-geos and has performance issues in PostGIS). Following geometries are currently
supported out-of-the-box:
* SRID 3857 (aka [Web Mercator](https://en.wikipedia.org/wiki/Web_Mercator_projection)
mentioned earlier).
* SRID 4326 (aka [WGS 84](https://en.wikipedia.org/wiki/World_Geodetic_System#1984_version), most
commonly used, default for GeoJSON [according to RFC 7946](https://www.rfc-editor.org/rfc/rfc7946.html)).

Additional projections can be described as
subclass of `SpatialProjectionInterface` and passed in `supports` function of a new class extended
from `AbstractProjectionRegistry`. Projection class should implement conversion of point coordinates on 2D
surface from WGS 84 to the required spatial system and the reverse transformation function.
## Example usage in Symfony:
### DI configuration:
```yaml
services:
  Brick\Geo\IO\GeoJSONReader: ~
  Brick\Geo\Engine\GeometryEngine:
    class: 'Brick\Geo\Engine\GEOSEngine'
  HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory: ~
  HeyMoon\VectorTileDataProvider\Factory\SourceFactory: ~
  HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry:
    class: 'HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry'
  HeyMoon\VectorTileDataProvider\Registry\AbstractExportFormatRegistry:
    class: 'HeyMoon\VectorTileDataProvider\Registry\ExportFormatRegistry'
  HeyMoon\VectorTileDataProvider\Service\SpatialService: ~
  HeyMoon\VectorTileDataProvider\Service\GridService: ~
  HeyMoon\VectorTileDataProvider\Service\TileService: ~
  HeyMoon\VectorTileDataProvider\Service\ExportService: ~
```
### Action:
```php
use Brick\Geo\IO\GeoJSONReader;
use Brick\Geo\Exception\GeometryException;
use HeyMoon\VectorTileDataProvider\Entity\TilePosition;
use HeyMoon\VectorTileDataProvider\Factory\SourceFactory;
use HeyMoon\VectorTileDataProvider\Service\GridService;
use HeyMoon\VectorTileDataProvider\Service\TileService;
use HeyMoon\VectorTileDataProvider\Service\ExportService;
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
            $type = $input->getOption('type') ?? 'mvt';
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
