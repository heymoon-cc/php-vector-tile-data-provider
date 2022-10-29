<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\MVTTools\Tests;

use Brick\Geo\IO\GeoJSONReader;
use Brick\Geo\Point;
use HeyMoon\MVTTools\Factory\GEOSServiceFactory;
use HeyMoon\MVTTools\Factory\SourceFactory;
use HeyMoon\MVTTools\Factory\TileFactory;
use HeyMoon\MVTTools\Helper\EncodingHelper;
use HeyMoon\MVTTools\Registry\BasicProjectionRegistry;
use HeyMoon\MVTTools\Registry\ExportFormatRegistry;
use HeyMoon\MVTTools\Service\ExportService;
use HeyMoon\MVTTools\Service\GridService;
use HeyMoon\MVTTools\Service\SpatialService;
use HeyMoon\MVTTools\Service\TileService;
use PHPUnit\Framework\TestCase;
use Vector_tile\Tile;
use Vector_tile\Tile\Layer;

abstract class BaseTestCase extends TestCase
{
    private GEOSServiceFactory $serviceFactory;
    private ExportFormatRegistry $exportFormatRegistry;
    private ?TileService $tileService = null;
    private ?ExportService $exportService = null;
    private ?SpatialService $spatialService = null;
    private ?TileFactory $tileFactory = null;
    private ?GridService $gridService = null;
    private ?SourceFactory $sourceFactory = null;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->serviceFactory = new GEOSServiceFactory();
        $this->exportFormatRegistry = new ExportFormatRegistry();
        parent::__construct($name, $data, $dataName);
    }

    public function assertFeaturesCount(int $expected, Tile $tile): void
    {
        $this->assertEquals($expected, $this->getFeaturesCount($tile));
    }

    public function assertPoint(Point $expected, Point $actual): void
    {
        $this->assertEquals($expected->x(), $actual->x());
        $this->assertEquals($expected->y(), $actual->y());
    }

    protected function getSourceFactory(): SourceFactory
    {
        return $this->sourceFactory ?? ($this->sourceFactory =
            $this->serviceFactory->getSourceFactory());
    }

    protected function getTileService(...$args): TileService
    {
        return $this->tileService ?? ($this->tileService = $this->serviceFactory->getTileService(...$args));
    }

    protected function getExportService(...$args): ExportService
    {
        return $this->exportService ??
            ($this->exportService = new ExportService($this->exportFormatRegistry, $this->getTileService(...$args)));
    }

    protected function getGridService(): GridService
    {
        return $this->gridService ?? ($this->gridService = $this->serviceFactory->getGridService());
    }

    protected function getSpatialService(): SpatialService
    {
        return $this->spatialService ?? ($this->spatialService = new SpatialService(new BasicProjectionRegistry()));
    }

    protected function getTileFactory(): TileFactory
    {
        return $this->tileFactory ?? ($this->tileFactory = new TileFactory());
    }

    protected function getGeoJSONReader(): GeoJSONReader
    {
        return new GeoJSONReader();
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getFixture(string $name): string
    {
        return EncodingHelper::getOriginalOrGZIP(file_get_contents(__DIR__."/fixtures/$name"));
    }

    protected function getFeaturesCount(Tile $tile): int
    {
        $featuresCount = 0;
        foreach ($tile->getLayers() as $layer) {
            /** @var Layer $layer */
            $featuresCount += $layer->getFeatures()->count();
        }
        return $featuresCount;
    }
}
