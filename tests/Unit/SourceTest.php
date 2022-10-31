<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace HeyMoon\VectorTileDataProvider\Tests\Unit;

use Brick\Geo\Point;
use HeyMoon\VectorTileDataProvider\Entity\AbstractSource;

class SourceTest extends AbstractSourceTest
{
    public function createSource(): AbstractSource
    {
        return $this->getSourceFactory()->create();
    }

    public function assertGeometryClass(): string
    {
        return Point::class;
    }
}
