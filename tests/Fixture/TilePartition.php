<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

namespace HeyMoon\VectorTileDataProvider\Tests\Fixture;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\GeometryIOException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use HeyMoon\VectorTileDataProvider\Entity\Source;
use HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection;
use HeyMoon\VectorTileDataProvider\Entity\Layer;
use HeyMoon\VectorTileDataProvider\Entity\Feature;
use HeyMoon\VectorTileDataProvider\Entity\TilePosition;

class TilePartition
{
    private function __construct(private readonly TilePosition $position, private readonly array $shapes) {}

    /**
     * @param Source $source
     * @param string $fixture
     * @return static
     * @throws CoordinateSystemException
     * @throws GeometryIOException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function load(Source $source, string $fixture): static
    {
        $data = json_decode($fixture, true);
        $layer = $source->getLayer('test');
        foreach ($data['data'] as $item) {
            $layer->add(
                Geometry::fromText($item['geometry'])->withSRID(WebMercatorProjection::SRID),
                $item['parameters']
            );
        }
        return new static(
            TilePosition::key($data['key'], $data['zoom']),
            $layer->getFeatures()
        );
    }

    /**
     * @return TilePosition
     */
    public function getPosition(): TilePosition
    {
        return $this->position;
    }

    /**
     * @return array
     */
    public function getFeatures(): array
    {
        return $this->shapes;
    }
}
