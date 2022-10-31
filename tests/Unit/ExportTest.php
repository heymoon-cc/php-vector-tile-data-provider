<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\VectorTileDataProvider\Tests\Unit;

use Exception;
use HeyMoon\VectorTileDataProvider\Service\ExportService;
use HeyMoon\VectorTileDataProvider\Tests\BaseTestCase;
use SVG\SVG;

class ExportTest extends BaseTestCase
{
    /**
     * @covers \HeyMoon\VectorTileDataProvider\Service\ExportService::convert
     * @covers \HeyMoon\VectorTileDataProvider\Export\AbstractExportFormat::isAvailable
     * @covers \HeyMoon\VectorTileDataProvider\Export\SvgExportFormat::export
     * @covers \HeyMoon\VectorTileDataProvider\Export\SvgExportFormat::getDependencyClass
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSpatialService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getTileService
     * @covers \HeyMoon\VectorTileDataProvider\Factory\GEOSServiceFactory::createEngine
     * @covers \HeyMoon\VectorTileDataProvider\Factory\TileFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\TileFactory::parse
     * @covers \HeyMoon\VectorTileDataProvider\Helper\EncodingHelper::getOriginalOrGZIP
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractExportFormatRegistry::get
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Registry\AbstractProjectionRegistry::addProjection
     * @covers \HeyMoon\VectorTileDataProvider\Registry\BasicProjectionRegistry::supports
     * @covers \HeyMoon\VectorTileDataProvider\Service\ExportService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\SpatialService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::decodeCommand
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::decodeValue
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getExtent
     * @covers \HeyMoon\VectorTileDataProvider\Service\TileService::getValues
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::get
     * @covers \HeyMoon\VectorTileDataProvider\Spatial\AbstractProjection::getSRID
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getSourceFactory
     * @covers \HeyMoon\VectorTileDataProvider\Factory\SourceFactory::__construct
     * @covers \HeyMoon\VectorTileDataProvider\Factory\AbstractServiceFactory::getGeometryCollectionFactory
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
