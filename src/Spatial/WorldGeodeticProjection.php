<?php

namespace HeyMoon\MVTTools\Spatial;

class WorldGeodeticProjection extends AbstractProjection
{
    public const SRID = 4326;

    protected function longitudeToWGS84(float $long): float
    {
        return $long;
    }

    protected function latitudeToWGS84(float $lat): float
    {
        return $lat;
    }

    protected function longitudeFromWGS84(float $long): float
    {
        return $long;
    }

    protected function latitudeFromWGS84(float $lat): float
    {
        return $lat;
    }
}