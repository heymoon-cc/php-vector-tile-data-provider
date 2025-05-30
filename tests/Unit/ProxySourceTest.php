<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\VectorTileDataProvider\Tests\Unit;

use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Proxy\PointProxy;
use HeyMoon\VectorTileDataProvider\Entity\AbstractSource;

class ProxySourceTest extends AbstractSourceTest
{
    /**
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::add
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getFeatureCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::add
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::count
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getName
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getSource
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Layer::addFeature
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::asGeoJSONFeature
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getFeatureParameters
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getId
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::getLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::count
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::getFeatures
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::addCollection
     * @covers \HeyMoon\VectorTileDataProvider\Entity\AbstractSource::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Source::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getProxySourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\ProxySourceFactory::create
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::createProxy
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @covers \HeyMoon\VectorTileDataProvider\Entity\Feature::setGeometry
     * @covers \HeyMoon\VectorTileDataProvider\Entity\ProxyLayer::addFeature
     * @covers \HeyMoon\VectorTileDataProvider\Entity\ProxySource::createLayer
     * @covers \HeyMoon\VectorTileDataProvider\Entity\ProxyLayer::add
     * @covers \HeyMoon\VectorTileDataProvider\Entity\ProxyLayer::getProxy
     * @throws GeometryException
     */
    public function testSource()
    {
        parent::testSource();
    }

    public function createSource(): AbstractSource
    {
        return $this->getProxySourceFactory()->create();
    }

    public function assertGeometryClass(): string
    {
        return PointProxy::class;
    }
}