<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\VectorTileDataProvider\Tests\Unit;

use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Point;
use HeyMoon\VectorTileDataProvider\Entity\AbstractSource;
use HeyMoon\VectorTileDataProvider\Tests\BaseTestCase;

abstract class AbstractSourceTest extends BaseTestCase
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
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::create
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @throws GeometryException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testSource()
    {
        $source = $this->createSource();
        foreach (range(1, 5) as $n) {
            $layerName = "test$n";
            while ($source->getLayer($layerName)->count() < 1000) {
                $source->add($layerName, Point::xy(rand(-180, 180),rand(-90, 90)));
            }
        }
        $this->assertCount(5000, $source);
        foreach (range(1, 5) as $n) {
            $layer = $source->getLayer("test$n");
            $this->assertEquals("test$n", $layer->getName());
            $this->assertCount(1000, $layer);
            $featureCollection = $layer->getFeatureCollection();
            $this->assertCount($layer->count(), $featureCollection->getFeatures());
            $new = $source->getLayer("new$n");
            $this->assertCount(0, $new);
            $new->addCollection($featureCollection);
            $this->assertCount($layer->count(), $new);
            $newFeatures = $new->getFeatures();
            $shapes = $layer->getFeatures();
            $this->assertEquals(1, array_key_first($shapes));
            $this->assertEquals(1000, array_key_last($shapes));
            foreach (array_keys($shapes) as $id) {
                $this->assertSame($shapes[$id]->getLayer(), $layer);
                $this->assertNotSame($newFeatures[$id]->getLayer(), $layer);
                $this->assertEquals($id, $shapes[$id]->getId());
                $this->assertEquals($id, $newFeatures[$id]->getId());
                $this->assertInstanceOf($this->assertGeometryClass(), $shapes[$id]->getGeometry());
                $this->assertInstanceOf($this->assertGeometryClass(), $newFeatures[$id]->getGeometry());
            }
        }
    }

    public abstract function createSource(): AbstractSource;

    public abstract function assertGeometryClass(): string;
}
