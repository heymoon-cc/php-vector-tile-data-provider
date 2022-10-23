<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\MVTTools\Tests\Unit;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Point;
use HeyMoon\MVTTools\Tests\BaseTestCase;
use HeyMoon\MVTTools\Service\SpatialService;
use HeyMoon\MVTTools\Spatial\AbstractProjection;

class SpatialTest extends BaseTestCase
{
    /**
     * Reference values from https://epsg.io/transform#s_srs=4326&t_srs=3857&x=37&y=55
     * @covers SpatialService::transform
     * @covers AbstractProjection::toWGS84
     * @covers AbstractProjection::fromWGS84
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
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
