<?php

namespace HeyMoon\VectorTileDataProvider\Tests\Unit;

use Brick\Geo\Exception\GeometryEngineException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use HeyMoon\VectorTileDataProvider\Entity\Feature;
use HeyMoon\VectorTileDataProvider\Entity\TilePosition;
use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use HeyMoon\VectorTileDataProvider\Tests\BaseTestCase;

class GridTest extends BaseTestCase
{
    /**
     * @covers \HeyMoon\VectorTileDataProvider\Service\GridService::getGrid
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Grid::iterate
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Grid::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::add
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getMinZoom
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::setGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getColumn
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getKey
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getZoom
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::key
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyz
     * @covers \HeyMoon\VectorTileDataProvider\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::get
     * @covers \HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\VectorTileDataProvider\Service\GridService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\GridService::getColumn
     * @covers \HeyMoon\VectorTileDataProvider\Service\GridService::getRow
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::check
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transform
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformLine
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformMultiPolygon
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformPoint
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformPolygon
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::fromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::get
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::isAligned
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::latitudeFromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::longitudeFromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getName
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getSource
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addFeature
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getParameters
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getFeatureParameters
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::asGeoJSONFeature
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getId
     * @covers \HeyMoon\VectorTileDataProvider\Entity\AbstractSource::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::create
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGridService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSpatialService
     * @throws GeometryException
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     */
    public function testGrid()
    {
        $service = $this->getGridService();
        $source = $this->getSourceFactory()->create();
        $source->addCollection('fixture',
            $this->getGeoFixture()->getFeatureCollection(10, 100)
        );
        $grid = $service->getGrid($source, 10);
        $i = 0;
        $grid->iterate(function (TilePosition $position, array $data) use (&$i) {
            $this->assertEquals(10, $position->getZoom());
            /** @var Feature[] $data */
            foreach ($data as $item) {
                $this->assertArrayNotHasKey('id', $item->getParameters());
                $feature = $item->asGeoJSONFeature();
                $this->assertObjectHasProperty('id', $feature->getProperties());
                $this->assertEquals($item->getId(), $feature->getProperties()->id);
            }
            $i++;
        });
        $this->assertEquals(2644, $i);
    }

    /**
     * @throws GeometryException
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws GeometryEngineException
     * @throws EmptyGeometryException
     * @throws InvalidGeometryException
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGridService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Grid::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Grid::get
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::add
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addFeature
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getMinZoom
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::setGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\AbstractSource::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::flipRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getColumn
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getKey
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getRow
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::getZoom
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyz
     * @covers \HeyMoon\VectorTileDataProvider\Entity\TilePosition::xyzFlip
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::create
     * @covers \HeyMoon\VectorTileDataProvider\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getGridSize
     * @covers \HeyMoon\VectorTileDataProvider\Helper\GeometryHelper::getTileWidth
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::get
     * @covers \HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\VectorTileDataProvider\Service\GridService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\GridService::getColumn
     * @covers \HeyMoon\VectorTileDataProvider\Service\GridService::getGrid
     * @covers \HeyMoon\VectorTileDataProvider\Service\GridService::getRow
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::check
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transform
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformLine
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformMultiPolygon
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformPoint
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::transformPolygon
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::fromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::get
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::isAligned
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::latitudeFromWGS84
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection::longitudeFromWGS84
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testMultiPoly()
    {
        $service = $this->getGridService();
        $source = $this->getSourceFactory()->create();
        $source->addCollection('fixture',
            $this->getGeoFixture()->getFeatureCollection(10, 100)
        );
        $grid = $service->getGrid($source, 9);
        $position = TilePosition::xyzFlip(255, 241, 9);
        $shapes = $grid->get($position);
        $this->assertCount(3, $shapes);
    }
}
