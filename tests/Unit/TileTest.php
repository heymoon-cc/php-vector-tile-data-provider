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

class TileTest extends BaseTestCase
{
    /**
     * @covers TileService::getTileMVT
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
        $partition = TilePartition::load($this->getFixture('partition.json.gz'));
        $tile = $tileService->getTileMVT($partition->getShapes(), $partition->getPosition());
        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertCount(1, $tile->getLayers());
    }

    /**
     * @covers TileService::getTileMVT
     * @throws GeometryException
     */
    public function testPolygons()
    {
        $tileService = $this->getTileService();
        $source = new Source();
        $source->addCollection('moscow',
            $this->getGeoJSONReader()->read($this->getFixture('moscow.json.gz'))
        );
        $tile = $tileService->getTileMVT($source->getShapes(), TilePosition::xyz(1, 1, 1));
        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertCount(1, $tile->getLayers());
        $this->assertFeaturesCount(33, $tile);
    }

    /**
     * @covers TileService::mergeLayers
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
     * @covers TilePosition::flipRow
     * @covers TilePosition::getBounds
     * @covers TilePosition::getMinPoint
     * @covers TilePosition::getMaxPoint
     * @covers GeometryHelper::getGridSize
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
        $this->assertEquals(0, TilePosition::parse($position, true)->getRow());
        $this->assertPoint(Point::xy(WebMercatorProjection::EARTH_RADIUS * -1, 0), $position->getMinPoint());
        $this->assertPoint(Point::xy(0, WebMercatorProjection::EARTH_RADIUS), $position->getMaxPoint());
        $this->assertEquals(2, TilePosition::clearRegistry());
    }
}
