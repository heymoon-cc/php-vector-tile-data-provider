<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\MVTTools\Tests\Unit;

use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Point;
use HeyMoon\MVTTools\Entity\Source;
use HeyMoon\MVTTools\Tests\BaseTestCase;

class SourceTest extends BaseTestCase
{
    /**
     * @covers \HeyMoon\MVTTools\Entity\Source::add
     * @covers \HeyMoon\MVTTools\Entity\Source::getLayer
     * @covers \HeyMoon\MVTTools\Entity\Layer::getFeatureCollection
     * @covers \HeyMoon\MVTTools\Entity\Layer::__construct
     * @covers \HeyMoon\MVTTools\Entity\Layer::add
     * @covers \HeyMoon\MVTTools\Entity\Layer::count
     * @covers \HeyMoon\MVTTools\Entity\Layer::getName
     * @covers \HeyMoon\MVTTools\Entity\Layer::getSource
     * @covers \HeyMoon\MVTTools\Entity\Layer::getShapes
     * @covers \HeyMoon\MVTTools\Entity\Layer::addCollection
     * @covers \HeyMoon\MVTTools\Entity\Layer::addFeature
     * @covers \HeyMoon\MVTTools\Entity\Shape::__construct
     * @covers \HeyMoon\MVTTools\Entity\Shape::asFeature
     * @covers \HeyMoon\MVTTools\Entity\Shape::getFeatureParameters
     * @covers \HeyMoon\MVTTools\Entity\Shape::getGeometry
     * @covers \HeyMoon\MVTTools\Entity\Shape::getId
     * @covers \HeyMoon\MVTTools\Entity\Shape::getLayer
     * @covers \HeyMoon\MVTTools\Entity\Source::count
     * @covers \HeyMoon\MVTTools\Entity\Source::getShapes
     * @covers \HeyMoon\MVTTools\Entity\Source::addCollection
     * @covers \HeyMoon\MVTTools\Entity\Source::__construct
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\MVTTools\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::__construct
     * @covers \HeyMoon\MVTTools\Factory\SourceFactory::create
     * @covers \HeyMoon\MVTTools\Factory\AbstractServiceFactory::getGeometryCollectionFactory
     * @throws GeometryException
     */
    public function testSource()
    {
        $factory = $this->getSourceFactory();
        $source = $factory->create();
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
            $newShapes = $new->getShapes();
            $shapes = $layer->getShapes();
            $this->assertEquals(1, array_key_first($shapes));
            $this->assertEquals(1000, array_key_last($shapes));
            foreach (array_keys($shapes) as $id) {
                $this->assertSame($shapes[$id]->getLayer(), $layer);
                $this->assertNotSame($newShapes[$id]->getLayer(), $layer);
                $this->assertEquals($id, $shapes[$id]->getId());
                $this->assertEquals($id, $newShapes[$id]->getId());
            }
        }
    }
}