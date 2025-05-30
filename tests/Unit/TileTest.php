<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\VectorTileDataProvider\Tests\Unit;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\GeometryEngineException;
use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\IO\GeoJSON\FeatureCollection;
use Brick\Geo\Point;
use Brick\Geo\Polygon;
use ErrorException;
use Exception;
use HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection;
use HeyMoon\VectorTileDataProvider\Tests\BaseTestCase;
use HeyMoon\VectorTileDataProvider\Entity\TilePosition;
use Vector_tile\Tile;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TileTest extends BaseTestCase
{
    /**
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getTileMVT
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getName
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getParameters
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getColumn
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getKey
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getZoom
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::key
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyz
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getMaxPoint
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getMinPoint
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getTileService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::check
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::addValues
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getValue
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::encodeCommand
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::encodeValue
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::simplify
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::get
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::add
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getSource
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addFeature
     * @covers \HeyMoon\VectorTileDataProvider\Entity\AbstractLayer::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\AbstractSource::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\AbstractSource::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\AbstractSource::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getId
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::setGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::create
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory::get
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory::getCollectionClass
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     * @throws UnexpectedGeometryException
     * @throws EmptyGeometryException
     * @throws GeometryEngineException
     * @throws Exception
     */
    public function testTile()
    {
        $tileService = $this->getTileService();
        $partition = $this->getSourceFactory()->create()->addCollection(
            'fixture',
            $this->getGeoFixture()->getFeatureCollection(10, 100)
        );
        $tile = $tileService->getTileMVT($partition->getFeatures(), TilePosition::xyz(0, 0, 0));
        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertCount(1, $tile->getLayers());
    }

    /**
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::mergeLayers
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getTileService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\TileFactory::parse
     * @covers \HeyMoon\VectorTileDataProvider\Factory\TileFactory::merge
     * @covers \HeyMoon\VectorTileDataProvider\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\TileFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::addValues
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getValue
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getExtent
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getValues
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::get
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @throws Exception
     */
    public function testMerge()
    {
        $factory = $this->getTileFactory();
        $tile = $factory->parse($this->getFixture('tile.mvt'));
        $this->assertCount(1, $tile->getLayers());
        $merged = $factory->merge($tile, $tile->serializeToString());
        $this->assertCount(2, $tile->getLayers());
        $this->assertCount(1, $merged->getLayers());
        $this->assertFeaturesCount(88, $tile);
        $this->assertFeaturesCount(44, $merged);
    }

    /**
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     * @throws ErrorException
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::flipRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getColumn
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getKey
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getMaxPoint
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getMinPoint
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getZoom
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyz
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyzFlip
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getTileService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\TileFactory::parse
     * @covers \HeyMoon\VectorTileDataProvider\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::get
     * @covers \HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformPoint
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::decodeCommand
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::decodeValue
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getValue
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getValues
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::get
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::isAligned
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::toWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::latitudeToWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::longitudeToWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::decodeGeometry
     */
    public function testGeometryDecode()
    {
        $factory = $this->getTileFactory();
        $tile = $factory->parse($this->getFixture('tile.mvt'));
        $this->assertGreaterThanOrEqual(1, $tile->getLayers()->count());
        foreach ($tile->getLayers() as $layer) {
            $collection = $this->getTileService()->decodeGeometry($layer, TilePosition::xyzFlip(619, 320, 10));
            $this->assertInstanceOf(FeatureCollection::class, $collection);
            $this->assertCount(44, $collection->getFeatures());
            foreach ($collection->getFeatures() as $feature) {
                foreach (['id', 'NAME'] as $property) {
                    $this->assertObjectHasProperty($property, $feature->getProperties());
                }
                $geometry = $feature->getGeometry();
                $this->assertInstanceOf(Polygon::class, $geometry);
                foreach ($geometry->rings() as $ring) {
                    foreach ($ring->points() as $point) {
                        $this->assertEquals(37.0, floor($point->x()));
                        $this->assertEquals(55.0, floor($point->y()));
                    }
                }
            }
        }
    }

    /**
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getTileMVT
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::add
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getName
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getParameters
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::setGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::setCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getColumn
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getKey
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getZoom
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyz
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::flipRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyzFlip
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getMaxPoint
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getMinPoint
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getTileService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::get
     * @covers \HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::check
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transform
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformLine
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformMultiPolygon
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformPoint
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformPolygon
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::addValues
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getValue
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::encodeCommand
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::encodeValue
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::simplify
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::fromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::get
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::isAligned
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::latitudeFromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::longitudeFromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getId
     * @covers \HeyMoon\VectorTileDataProvider\Entity\AbstractSource::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getSource
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addFeature
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::create
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory::get
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GeometryCollectionFactory::getCollectionClass
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @throws GeometryException
     */
    public function testPolygons()
    {
        $tileService = $this->getTileService();
        $source = $this->getSourceFactory()->create();
        $source->addCollection('fixture',
            $this->getGeoFixture()->getFeatureCollection(10, 100)
        );
        $shapes = $this->getSpatialService()->check($source->getFeatures(), WebMercatorProjection::SRID);
        $tile = $tileService->getTileMVT($shapes, TilePosition::xyz(1, 1, 1));
        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertCount(1, $tile->getLayers());
        $this->assertFeaturesCount(49, $tile);
        $tile = $tileService->getTileMVT($shapes, TilePosition::xyzFlip(618, 320, 10));
        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertCount(1, $tile->getLayers());
        $this->assertFeaturesCount(33, $tile);
    }

    /**
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::flipRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::__toString
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::clearRegistry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getColumn
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getKey
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getMaxPoint
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getMinPoint
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getZoom
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::key
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::keyFlip
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::parse
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::parseFlip
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyz
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyzFlip
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getTileWidth
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
