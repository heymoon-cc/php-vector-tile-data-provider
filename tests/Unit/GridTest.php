<?php

namespace Unit;

use HeyMoon\MVTTools\Entity\Grid;
use HeyMoon\MVTTools\Service\GridService;
use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use HeyMoon\MVTTools\Entity\Source;
use HeyMoon\MVTTools\Tests\BaseTestCase;

class GridTest extends BaseTestCase
{
    /**
     * @covers \HeyMoon\MVTTools\Service\GridService::getGrid
     * @covers \HeyMoon\MVTTools\Entity\Grid::iterate
     * @covers \HeyMoon\MVTTools\Entity\Grid::__construct
     * @covers \HeyMoon\MVTTools\Entity\Layer::__construct
     * @covers \HeyMoon\MVTTools\Entity\Layer::add
     * @covers \HeyMoon\MVTTools\Entity\Layer::addCollection
     * @covers \HeyMoon\MVTTools\Entity\Layer::getShapes
     * @covers \HeyMoon\MVTTools\Entity\Shape::__construct
     * @covers \HeyMoon\MVTTools\Entity\Shape::getGeometry
     * @covers \HeyMoon\MVTTools\Entity\Shape::getMinZoom
     * @covers \HeyMoon\MVTTools\Entity\Shape::setGeometry
     * @covers \HeyMoon\MVTTools\Entity\Source::addCollection
     * @covers \HeyMoon\MVTTools\Entity\Source::getLayer
     * @covers \HeyMoon\MVTTools\Entity\Source::getShapes
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::__construct
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getColumn
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getKey
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getRow
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getZoom
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::key
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::xyz
     * @covers \HeyMoon\MVTTools\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getBounds
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getLineBounds
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getTileWidth
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::get
     * @covers \HeyMoon\MVTTools\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\MVTTools\Service\GridService::__construct
     * @covers \HeyMoon\MVTTools\Service\GridService::getColumn
     * @covers \HeyMoon\MVTTools\Service\GridService::getRow
     * @covers \HeyMoon\MVTTools\Service\SpatialService::__construct
     * @covers \HeyMoon\MVTTools\Service\SpatialService::check
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transform
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transformLine
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transformMultiPolygon
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transformPoint
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transformPolygon
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::fromWGS84
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::get
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::isAligned
     * @covers \HeyMoon\MVTTools\Spatial\WebMercatorProjection::latitudeFromWGS84
     * @covers \HeyMoon\MVTTools\Spatial\WebMercatorProjection::longitudeFromWGS84
     * @throws GeometryException
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     */
    public function testGrid()
    {
        $service = $this->getGridService();
        $source = new Source();
        $source->addCollection('moscow',
            $this->getGeoJSONReader()->read($this->getFixture('moscow.json.gz'))
        );
        $grid = $service->getGrid($source, 10);
        $i = 0;
        $grid->iterate(function () use (&$i) {
            $i++;
        });
        $this->assertEquals(10, $i);
    }
}
