<?php

namespace HeyMoon\MVTTools\Entity;

class Grid
{
    public function __construct(
        private readonly int $zoom,
        private readonly array $data
    ) {}

    public function iterate(callable $callback): self
    {
        foreach ($this->data as $key => $item) {
            $callback(TilePosition::key($key, $this->zoom), $item);
        }
        return $this;
    }

    public function count(): int
    {
        return count($this->data);
    }
}
