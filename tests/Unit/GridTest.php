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
     * @covers GridService::getGrid
     * @covers Grid::iterate
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
