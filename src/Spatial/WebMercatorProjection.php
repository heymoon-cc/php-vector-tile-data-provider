<?php

namespace HeyMoon\VectorTileDataProvider\Spatial;

/**
 * https://stackoverflow.com/a/40403522
 */
class WebMercatorProjection extends AbstractProjection
{
    public const EARTH_RADIUS = 20037508.34;

    const E = 2.7182818284;

    public const SRID = 3857;

    protected function longitudeToWGS84(float $long): float
    {
        return $long * 180 / self::EARTH_RADIUS;
    }

    protected function latitudeToWGS84(float $lat): float
    {
        $value = $lat / (self::EARTH_RADIUS / 180);
        $exponent = (pi() / 180) * $value;
        return atan(pow(self::E, $exponent)) / (pi() / 360) - 90;
    }

    protected function longitudeFromWGS84(float $long): float
    {
        return $long * self::EARTH_RADIUS / 180;
    }

    protected function latitudeFromWGS84(float $lat): float
    {
        return log(tan((90 + $lat) * pi() / 360)) / (pi() / 180) * (self::EARTH_RADIUS / 180);
    }
}