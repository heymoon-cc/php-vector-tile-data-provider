<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\VectorTileDataProvider\Tests\Unit;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Point;
use HeyMoon\VectorTileDataProvider\Tests\BaseTestCase;

class SpatialTest extends BaseTestCase
{
    /**
     * Reference values from https://epsg.io/transform#s_srs=4326&t_srs=3857&x=37&y=55
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transform
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::toWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::fromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::get
     * @covers \HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformPoint
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::get
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::isAligned
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::latitudeFromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::latitudeToWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::longitudeFromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::longitudeToWGS84
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testSpatial()
    {
        $service = $this->getSpatialService();
        $point = Point::xy(37, 55, 4326);
        $transformed = $service->transform($point, 3857);
        $this->assertInstanceOf(Point::class, $transformed);
        $this->assertEquals(4118821, round($transformed->x()));
        $this->assertEquals(7361866, round($transformed->y()));
        $reversed = $service->transform($transformed, $point->SRID());
        $this->assertInstanceOf(Point::class, $reversed);
        $this->assertEquals(round($point->x()), round($reversed->x()));
        $this->assertEquals(round($point->y()), round($reversed->y()));
    }
}
