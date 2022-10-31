<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Grid
{
    public function __construct(
        private readonly int $zoom,
        private readonly array $data
    ) {}

    /**
     * @param callable $callback
     * @return $this
     */
    public function iterate(callable $callback): self
    {
        foreach ($this->data as $key => $item) {
            $callback(TilePosition::key($key, $this->zoom), $item);
        }
        return $this;
    }

    /**
     * @param TilePosition $position
     * @return Feature[]
     */
    public function get(TilePosition $position): array
    {
        return ($position->getZoom() === $this->zoom) ? ($this->data[$position->getKey()] ?? []) : [];
    }

    public function count(): int
    {
        return count($this->data);
    }
}
