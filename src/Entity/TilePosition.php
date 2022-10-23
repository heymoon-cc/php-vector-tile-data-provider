<?php

namespace HeyMoon\MVTTools\Entity;

use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Point;
use Brick\Geo\Polygon;
use HeyMoon\MVTTools\Helper\GeometryHelper;
use Stringable;

class TilePosition implements Stringable
{
    private static array $registry = [];
    private ?float $tileWidth = null;
    private ?int  $gridSize = null;
    private ?string $query = null;
    private ?Polygon $border = null;
    private ?array $bounds = null;
    private ?int $key = null;

    private function __construct(
        private readonly int $column,
        private readonly int $row,
        private readonly int $zoom
    ) {}

    public function flipRow(): int
    {
        return ($this->getGridSize() - 1) - $this->getRow();
    }

    public function getKey(): int
    {
        return $this->key ?? ($this->key = $this->getColumn() * $this->getGridSize() + $this->getRow());
    }

    /**
     * @return int
     */
    public function getColumn(): int
    {
        return $this->column;
    }

    /**
     * @return int
     */
    public function getRow(): int
    {
        return $this->row;
    }

    /**
     * @return int
     */
    public function getZoom(): int
    {
        return $this->zoom;
    }

    /**
     * @return float
     */
    public function getTileWidth(): float
    {
        return $this->tileWidth ?? ($this->tileWidth = GeometryHelper::getTileWidth($this->zoom));
    }

    public function getGridSize(): int
    {
        return $this->gridSize ?? ($this->gridSize = GeometryHelper::getGridSize($this->getZoom()));
    }

    /**
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public function getBorder(): Polygon
    {
        return $this->border ?? ($this->border = GeometryHelper::getTileBorder($this));
    }

    /**
     * @throws EmptyGeometryException
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public function getBounds(): array
    {
        return $this->bounds ?? ($this->bounds = GeometryHelper::getBounds($this->getBorder()));
    }

    /**
     * @throws EmptyGeometryException
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public function getMinPoint(): Point
    {
        return $this->getBounds()[0];
    }

    /**
     * @throws EmptyGeometryException
     * @throws CoordinateSystemException
     * @throws InvalidGeometryException
     */
    public function getMaxPoint(): Point
    {
        return $this->getBounds()[1];
    }

    public function __toString(): string
    {
        return $this->query ?? $this->query = http_build_query([
            'c' => $this->getColumn(),
            'r' => $this->getRow(),
            'z' => $this->getZoom()
        ]);
    }

    public static function parse(string $data, bool $flipRow = false): static
    {
        parse_str($data, $parsed);
        return static::xyz($parsed['c'], $parsed['r'], $parsed['z'], $flipRow);
    }

    public static function xyz(int $x, int $y, int $z, bool $flipRow = false): static
    {
        $new = new static($x, $flipRow ? (new static($x, $y, $z))->flipRow() : $y, $z);
        if (!array_key_exists($z, static::$registry)) {
            static::$registry[$z] = [];
        }
        return static::$registry[$z][$new->getKey()] ?? (static::$registry[$z][$new->getKey()] = $new);
    }

    public static function key(int $key, int $zoom, bool $flipRow = false): TilePosition
    {
        $dimensions = GeometryHelper::getGridSize($zoom);
        $column = $dimensions ? floor($key / $dimensions) : 0;
        $row = $dimensions ? $key % $dimensions : 0;
        return static::xyz($column, $row, $zoom, $flipRow);
    }

    public static function clearRegistry(): int
    {
        $count = array_reduce(static::$registry, fn(int $c, array $item) => $c + count($item), 0);
        static::$registry = [];
        return $count;
    }
}
