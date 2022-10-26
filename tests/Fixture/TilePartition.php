<?php
/** @noinspection PhpIllegalPsrClassPathInspection */

namespace HeyMoon\MVTTools\Tests\Fixture;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\GeometryIOException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use HeyMoon\MVTTools\Spatial\WebMercatorProjection;
use HeyMoon\MVTTools\Entity\Layer;
use HeyMoon\MVTTools\Entity\Shape;
use HeyMoon\MVTTools\Entity\TilePosition;

class TilePartition
{
    private function __construct(private readonly TilePosition $position, private readonly array $shapes) {}

    /**
     * @param string $fixture
     * @return static
     * @throws CoordinateSystemException
     * @throws GeometryIOException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function load(string $fixture): static
    {
        $data = json_decode($fixture, true);
        $layer = new Layer('test');
        return new static(
            TilePosition::key($data['key'], $data['zoom']),
            array_map(fn (array $item) => new Shape(
                $layer,
                Geometry::fromText($item['geometry'])->withSRID(WebMercatorProjection::SRID),
                $item['parameters']
            ), $data['data'])
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
    public function getShapes(): array
    {
        return $this->shapes;
    }
}
