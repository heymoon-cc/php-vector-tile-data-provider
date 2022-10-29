<?php

namespace Unit;

use Brick\Geo\Exception\GeometryEngineException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use HeyMoon\MVTTools\Entity\Grid;
use HeyMoon\MVTTools\Entity\Shape;
use HeyMoon\MVTTools\Entity\TilePosition;
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
     * @covers \HeyMoon\MVTTools\Entity\Layer::getName
     * @covers \HeyMoon\MVTTools\Entity\Layer::getSource
     * @covers \HeyMoon\MVTTools\Entity\Layer::addFeature
     * @covers \HeyMoon\MVTTools\Entity\Shape::getParameters
     * @covers \HeyMoon\MVTTools\Entity\Shape::getFeatureParameters
     * @covers \HeyMoon\MVTTools\Entity\Shape::asFeature
     * @covers \HeyMoon\MVTTools\Entity\Shape::getId
     * @covers \HeyMoon\MVTTools\Entity\Source::__construct
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\MVTTools\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::__construct
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::create
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getGridService
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSpatialService
     * @throws GeometryException
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     */
    public function testGrid()
    {
        $service = $this->getGridService();
        $source = $this->getSourceFactory()->create();
        $source->addCollection('moscow',
            $this->getGeoJSONReader()->read($this->getFixture('moscow.json.gz'))
        );
        $grid = $service->getGrid($source, 10);
        $i = 0;
        $grid->iterate(function (TilePosition $position, array $data) use (&$i) {
            /** @var Shape[] $data */
            foreach ($data as $item) {
                $this->assertArrayNotHasKey('id', $item->getParameters());
                $feature = $item->asFeature();
                $this->assertObjectHasAttribute('id', $feature->getProperties());
                $this->assertEquals($item->getId(), $feature->getProperties()->id);
            }
            $i++;
        });
        $this->assertEquals(10, $i);
    }

    /**
     * @throws GeometryException
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws GeometryEngineException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getGridService
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\MVTTools\Entity\Grid::__construct
     * @covers \HeyMoon\MVTTools\Entity\Grid::get
     * @covers \HeyMoon\MVTTools\Entity\Layer::__construct
     * @covers \HeyMoon\MVTTools\Entity\Layer::add
     * @covers \HeyMoon\MVTTools\Entity\Layer::addCollection
     * @covers \HeyMoon\MVTTools\Entity\Layer::addFeature
     * @covers \HeyMoon\MVTTools\Entity\Layer::getShapes
     * @covers \HeyMoon\MVTTools\Entity\Shape::__construct
     * @covers \HeyMoon\MVTTools\Entity\Shape::getGeometry
     * @covers \HeyMoon\MVTTools\Entity\Shape::getMinZoom
     * @covers \HeyMoon\MVTTools\Entity\Shape::setGeometry
     * @covers \HeyMoon\MVTTools\Entity\Source::__construct
     * @covers \HeyMoon\MVTTools\Entity\Source::addCollection
     * @covers \HeyMoon\MVTTools\Entity\Source::getLayer
     * @covers \HeyMoon\MVTTools\Entity\Source::getShapes
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::__construct
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::flipRow
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getColumn
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getKey
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getRow
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getZoom
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::xyz
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::xyzFlip
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\MVTTools\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::__construct
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::create
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
     * @covers \HeyMoon\MVTTools\Service\GridService::getGrid
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
     */
    public function testMultiPoly()
    {
        $service = $this->getGridService();
        $source = $this->getSourceFactory()->create();
        $source->addCollection('moscow',
            $this->getGeoJSONReader()->read($this->getFixture('mo.json.gz'))
        );
        $grid = $service->getGrid($source, 9);
        $position = TilePosition::xyzFlip(309, 160, 9);
        $shapes = $grid->get($position);
        $this->assertCount(92, $shapes);
    }
}
