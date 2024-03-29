<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

use Brick\Geo\Geometry;
use Brick\Geo\Proxy\ProxyInterface;

class ProxyLayer extends AbstractLayer
{
    public function add(Geometry $geometry, array $properties = [], int $minZoom = 0, ?int $id = null): AbstractLayer
    {
        return parent::add($this->getProxy($geometry), $properties, $minZoom, $id);
    }

    public function addFeature(Feature $feature, ?int $id = null): int
    {
        $return = parent::addFeature($feature, $id);
        $geometry = $this->features[$return]->getGeometry();
        if ($geometry instanceof ProxyInterface) {
            return $return;
        }
        $this->features[$return] = $this->features[$return]
            ->setGeometry($this->getProxy($geometry));
        return $return;
    }

    private function getProxy(Geometry $geometry): Geometry|ProxyInterface
    {
        if ($geometry instanceof ProxyInterface) {
            return $geometry;
        }
        $class = explode('\\', $geometry::class);
        $proxyClass = array_pop($class).'Proxy';
        $proxyClass = implode('\\', $class)."\\Proxy\\$proxyClass";
        /** @var Geometry|ProxyInterface $proxy */
        return new $proxyClass($geometry->asBinary(), true, $geometry->SRID());
    }
}
