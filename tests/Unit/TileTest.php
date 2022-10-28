<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\MVTTools\Tests\Unit;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\GeometryEngineException;
use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Exception\GeometryIOException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Point;
use Exception;
use HeyMoon\MVTTools\Helper\GeometryHelper;
use HeyMoon\MVTTools\Spatial\WebMercatorProjection;
use HeyMoon\MVTTools\Tests\BaseTestCase;
use HeyMoon\MVTTools\Tests\Fixture\TilePartition;
use HeyMoon\MVTTools\Service\TileService;
use HeyMoon\MVTTools\Entity\Source;
use HeyMoon\MVTTools\Entity\TilePosition;
use Vector_tile\Tile;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TileTest extends BaseTestCase
{
    /**
     * @covers \HeyMoon\MVTTools\Service\TileService::getTileMVT
     * @covers \HeyMoon\MVTTools\Entity\Layer::__construct
     * @covers \HeyMoon\MVTTools\Entity\Layer::getName
     * @covers \HeyMoon\MVTTools\Entity\Shape::__construct
     * @covers \HeyMoon\MVTTools\Entity\Shape::getGeometry
     * @covers \HeyMoon\MVTTools\Entity\Shape::getLayer
     * @covers \HeyMoon\MVTTools\Entity\Shape::getParameters
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::__construct
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getBorder
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getColumn
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getKey
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getRow
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getTileWidth
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getZoom
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::key
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::xyz
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getTileService
     * @covers \HeyMoon\MVTTools\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\MVTTools\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getBounds
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getLineBounds
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getTileBorder
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getTileWidth
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\MVTTools\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\MVTTools\Service\SpatialService::__construct
     * @covers \HeyMoon\MVTTools\Service\SpatialService::check
     * @covers \HeyMoon\MVTTools\Service\TileService::__construct
     * @covers \HeyMoon\MVTTools\Service\TileService::addValues
     * @covers \HeyMoon\MVTTools\Service\TileService::createLayer
     * @covers \HeyMoon\MVTTools\Service\TileService::encodeCommand
     * @covers \HeyMoon\MVTTools\Service\TileService::encodeValue
     * @covers \HeyMoon\MVTTools\Service\TileService::simplify
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::get
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\MVTTools\Entity\Layer::add
     * @covers \HeyMoon\MVTTools\Entity\Layer::getShapes
     * @covers \HeyMoon\MVTTools\Entity\Layer::getSource
     * @covers \HeyMoon\MVTTools\Entity\Layer::addFeature
     * @covers \HeyMoon\MVTTools\Entity\Source::__construct
     * @covers \HeyMoon\MVTTools\Entity\Shape::getId
     * @covers \HeyMoon\MVTTools\Entity\Source::getLayer
     * @covers \HeyMoon\MVTTools\Entity\Shape::setGeometry
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::__construct
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::create
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @covers \HeyMoon\MVTTools\Factory\GeometryCollectionFactory::get
     * @covers \HeyMoon\MVTTools\Factory\GeometryCollectionFactory::getCollectionClass
     * @throws CoordinateSystemException
     * @throws GeometryIOException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     * @throws EmptyGeometryException
     * @throws GeometryEngineException
     * @throws Exception
     */
    public function testTile()
    {
        $tileService = $this->getTileService();
        $partition = TilePartition::load(
            $this->getSourceFactory()->create(), $this->getFixture('partition.json.gz')
        );
        $tile = $tileService->getTileMVT($partition->getShapes(), $partition->getPosition());
        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertCount(1, $tile->getLayers());
    }

    /**
     * @covers \HeyMoon\MVTTools\Service\TileService::mergeLayers
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getTileService
     * @covers \HeyMoon\MVTTools\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\MVTTools\Factory\TileFactory::parse
     * @covers \HeyMoon\MVTTools\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\MVTTools\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\MVTTools\Service\SpatialService::__construct
     * @covers \HeyMoon\MVTTools\Service\TileService::__construct
     * @covers \HeyMoon\MVTTools\Service\TileService::addValues
     * @covers \HeyMoon\MVTTools\Service\TileService::createLayer
     * @covers \HeyMoon\MVTTools\Service\TileService::getExtent
     * @covers \HeyMoon\MVTTools\Service\TileService::getValues
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::get
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::__construct
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @throws Exception
     */
    public function testMerge()
    {
        $tileService = $this->getTileService();
        $tile = $this->getTileFactory()->parse($this->getFixture('tile.mvt'));
        $this->assertCount(1, $tile->getLayers());
        $tile->mergeFromString($tile->serializeToString());
        $merged = $tileService->mergeLayers($tile);
        $this->assertCount(2, $tile->getLayers());
        $this->assertCount(1, $merged->getLayers());
        $this->assertFeaturesCount(21240, $tile);
        $this->assertFeaturesCount(10594, $merged);
    }

    /**
     * @covers \HeyMoon\MVTTools\Service\TileService::getTileMVT
     * @covers \HeyMoon\MVTTools\Entity\Layer::__construct
     * @covers \HeyMoon\MVTTools\Entity\Layer::add
     * @covers \HeyMoon\MVTTools\Entity\Layer::addCollection
     * @covers \HeyMoon\MVTTools\Entity\Layer::getName
     * @covers \HeyMoon\MVTTools\Entity\Layer::getShapes
     * @covers \HeyMoon\MVTTools\Entity\Shape::__construct
     * @covers \HeyMoon\MVTTools\Entity\Shape::getGeometry
     * @covers \HeyMoon\MVTTools\Entity\Shape::getLayer
     * @covers \HeyMoon\MVTTools\Entity\Shape::getParameters
     * @covers \HeyMoon\MVTTools\Entity\Shape::setGeometry
     * @covers \HeyMoon\MVTTools\Entity\Source::addCollection
     * @covers \HeyMoon\MVTTools\Entity\Source::getLayer
     * @covers \HeyMoon\MVTTools\Entity\Source::getShapes
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::__construct
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getBorder
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getColumn
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getKey
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getRow
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getTileWidth
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getZoom
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::xyz
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::flipRow
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::xyzFlip
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getTileService
     * @covers \HeyMoon\MVTTools\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\MVTTools\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getBounds
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getLineBounds
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getTileBorder
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getTileWidth
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::get
     * @covers \HeyMoon\MVTTools\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\MVTTools\Service\SpatialService::__construct
     * @covers \HeyMoon\MVTTools\Service\SpatialService::check
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transform
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transformLine
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transformMultiPolygon
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transformPoint
     * @covers \HeyMoon\MVTTools\Service\SpatialService::transformPolygon
     * @covers \HeyMoon\MVTTools\Service\TileService::__construct
     * @covers \HeyMoon\MVTTools\Service\TileService::addValues
     * @covers \HeyMoon\MVTTools\Service\TileService::createLayer
     * @covers \HeyMoon\MVTTools\Service\TileService::encodeCommand
     * @covers \HeyMoon\MVTTools\Service\TileService::encodeValue
     * @covers \HeyMoon\MVTTools\Service\TileService::simplify
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::fromWGS84
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::get
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::isAligned
     * @covers \HeyMoon\MVTTools\Spatial\WebMercatorProjection::latitudeFromWGS84
     * @covers \HeyMoon\MVTTools\Spatial\WebMercatorProjection::longitudeFromWGS84
     * @covers \HeyMoon\MVTTools\Entity\Shape::getId
     * @covers \HeyMoon\MVTTools\Entity\Source::__construct
     * @covers \HeyMoon\MVTTools\Entity\Layer::getSource
     * @covers \HeyMoon\MVTTools\Entity\Layer::addFeature
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::__construct
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::create
     * @covers \HeyMoon\MVTTools\Factory\GeometryCollectionFactory::get
     * @covers \HeyMoon\MVTTools\Factory\GeometryCollectionFactory::getCollectionClass
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @throws GeometryException
     */
    public function testPolygons()
    {
        $tileService = $this->getTileService();
        $source = $this->getSourceFactory()->create();
        $source->addCollection('moscow',
            $this->getGeoJSONReader()->read($this->getFixture('moscow.json.gz'))
        );
        $shapes = $this->getSpatialService()->check($source->getShapes(), WebMercatorProjection::SRID);
        $tile = $tileService->getTileMVT($shapes, TilePosition::xyz(1, 1, 1));
        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertCount(1, $tile->getLayers());
        $this->assertFeaturesCount(33, $tile);
        $tile = $tileService->getTileMVT($shapes, TilePosition::xyzFlip(618, 320, 10));
        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertCount(1, $tile->getLayers());
        $this->assertFeaturesCount(43, $tile);
    }

    /**
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::flipRow
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::__construct
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::__toString
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::clearRegistry
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getBorder
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getBounds
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getColumn
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getKey
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getMaxPoint
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getMinPoint
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getRow
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getTileWidth
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::getZoom
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::key
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::keyFlip
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::parse
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::parseFlip
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::xyz
     * @covers \HeyMoon\MVTTools\Entity\TilePosition::xyzFlip
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getBounds
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getLineBounds
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getTileBorder
     * @covers \HeyMoon\MVTTools\Helper\GeometryHelper::getTileWidth
     * @throws EmptyGeometryException
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public function testPosition()
    {
        TilePosition::clearRegistry();
        $position = TilePosition::xyz(0, 1, 1);
        $this->assertEquals(1, $position->getRow());
        $this->assertEquals(1, $position->getKey());
        $this->assertEquals((string)$position, (string)TilePosition::key($position->getKey(), $position->getZoom()));
        $this->assertEquals((string)$position, TilePosition::parse($position));
        $parseFlip = TilePosition::parseFlip($position);
        $this->assertEquals(0, $parseFlip->getRow());
        $this->assertEquals((string)$parseFlip, TilePosition::keyFlip($position->getKey(), $position->getZoom()));
        $this->assertPoint(Point::xy(WebMercatorProjection::EARTH_RADIUS * -1, 0), $position->getMinPoint());
        $this->assertPoint(Point::xy(0, WebMercatorProjection::EARTH_RADIUS), $position->getMaxPoint());
        $this->assertEquals(2, TilePosition::clearRegistry());
    }
}
