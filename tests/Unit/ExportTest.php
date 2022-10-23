<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\MVTTools\Tests\Unit;

use Exception;
use HeyMoon\MVTTools\Service\ExportService;
use HeyMoon\MVTTools\Tests\BaseTestCase;
use SVG\SVG;
use Vector_tile\Tile\Layer;

class ExportTest extends BaseTestCase
{
    /**
     * @covers ExportService::convert
     * @throws Exception
     */
    public function testExport()
    {
        $tile = $this->getTileFactory()->parse($this->getFixture('tile.mvt'));
        $exportService = $this->getExportService();
        $svg = $exportService->convert($tile);
        $this->assertInstanceOf(SVG::class, $svg);
        $this->assertEquals($this->getTileService()->getExtent($tile), $svg->getDocument()->getHeight());
        $this->assertFeaturesCount($svg->getDocument()->countChildren() - 1, $tile);
    }
}
