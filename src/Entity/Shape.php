<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Geometry;
use Stringable;

class Shape implements Stringable
{
    private string $uuid;

    public function __construct(
        private readonly Layer    $layer,
        private Geometry $geometry,
        private readonly array    $parameters = [],
        private readonly int      $minZoom = 0,
        ?string                   $uuid = null
    )
    {
        if ($uuid) {
            $this->uuid = $uuid;
        } else {
            $this->uuid = uniqid();
        }
    }

    /**
     * @return int
     */
    public function getMinZoom(): int
    {
        return $this->minZoom;
    }

    /**
     * @return Geometry
     */
    public function getGeometry(): Geometry
    {
        return $this->geometry;
    }

    public function setGeometry(Geometry $geometry): self
    {
        $this->geometry = $geometry;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter($key): mixed
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * @return Layer
     */
    public function getLayer(): Layer
    {
        return $this->layer;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
