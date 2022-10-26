<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\MVTTools\Tests\Unit;

use Exception;
use HeyMoon\MVTTools\Service\ExportService;
use HeyMoon\MVTTools\Tests\BaseTestCase;
use SVG\SVG;

class ExportTest extends BaseTestCase
{
    /**
     * @covers \HeyMoon\MVTTools\Service\ExportService::convert
     * @covers \HeyMoon\MVTTools\Export\AbstractExportFormat::isAvailable
     * @covers \HeyMoon\MVTTools\Export\SvgExportFormat::export
     * @covers \HeyMoon\MVTTools\Export\SvgExportFormat::getDependencyClass
     * @covers \HeyMoon\MVTTools\Factory\AbstractTileServiceFactory::getEngine
     * @covers \HeyMoon\MVTTools\Factory\AbstractTileServiceFactory::getSpatialService
     * @covers \HeyMoon\MVTTools\Factory\AbstractTileServiceFactory::getTileService
     * @covers \HeyMoon\MVTTools\Factory\GEOSTileServiceFactory::createEngine
     * @covers \HeyMoon\MVTTools\Factory\TileFactory::parse
     * @covers \HeyMoon\MVTTools\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\MVTTools\Registry\AbstractExportFormatRegistry::get
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\MVTTools\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\MVTTools\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\MVTTools\Service\ExportService::__construct
     * @covers \HeyMoon\MVTTools\Service\SpatialService::__construct
     * @covers \HeyMoon\MVTTools\Service\TileService::__construct
     * @covers \HeyMoon\MVTTools\Service\TileService::decodeCommand
     * @covers \HeyMoon\MVTTools\Service\TileService::decodeValue
     * @covers \HeyMoon\MVTTools\Service\TileService::getExtent
     * @covers \HeyMoon\MVTTools\Service\TileService::getValues
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::get
     * @covers \HeyMoon\MVTTools\Spatial\AbstractProjection::getSRID
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
