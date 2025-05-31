<?php

namespace HeyMoon\VectorTileDataProvider\Registry;

use HeyMoon\VectorTileDataProvider\Contract\ProjectionRegistryInterface;
use HeyMoon\VectorTileDataProvider\Spatial\SpatialProjectionInterface;
use HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection;
use HeyMoon\VectorTileDataProvider\Spatial\WorldGeodeticProjection;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
abstract class AbstractProjectionRegistry implements ProjectionRegistryInterface
{
    private array $projections = [];

    public function __construct()
    {
        foreach ($this->supports() as $projection) {
            $this->addProjection($projection);
        }
        if (!array_key_exists(WebMercatorProjection::SRID, $this->projections)) {
            $this->addProjection(WebMercatorProjection::get());
        }
        if (!array_key_exists(WorldGeodeticProjection::SRID, $this->projections)) {
            $this->addProjection(WorldGeodeticProjection::get());
        }
    }

    public function addProjection(SpatialProjectionInterface $projection): static
    {
        $this->projections[$projection->getSRID()] = $projection;
        return $this;
    }

    public function get(int $srid): ?SpatialProjectionInterface
    {
        return $this->projections[$srid] ?? null;
    }

    protected abstract function supports(): array;
}
