<?php

namespace HeyMoon\VectorTileDataProvider\Entity;

use Brick\Geo\Point;
use HeyMoon\VectorTileDataProvider\Helper\GeometryHelper;
use HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection;
use Stringable;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TilePosition implements Stringable
{
    private $column;
    private $row;
    private static array $registry = [];
    private ?float $tileWidth = null;
    private ?int  $gridSize = null;
    private ?string $query = null;
    private ?int $key = null;
    private ?Point $minPoint = null;
    private ?Point $maxPoint = null;

    private function __construct(
        int $column,
        int $row,
        private readonly int $zoom
    ) {
        $size = $this->getGridSize();
        $this->column = $column < 0 ? $size + $column :
            ($column >= $size ? $column - $size : $column);
        $this->row = $row < 0 ? $size + $row :
            ($row >= $size ? $row - $size : $row);
    }

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

    public function __toString(): string
    {
        return $this->query ?? $this->query = http_build_query([
            'c' => $this->getColumn(),
            'r' => $this->getRow(),
            'z' => $this->getZoom()
        ]);
    }

    public static function parse(string $data): static
    {
        parse_str($data, $parsed);
        return static::xyz($parsed['c'], $parsed['r'], $parsed['z']);
    }

    public static function parseFlip(string $data): static
    {
        parse_str($data, $parsed);
        return static::xyzFlip($parsed['c'], $parsed['r'], $parsed['z']);
    }

    public static function xyz(int $x, int $y, int $z): static
    {
        $new = new static($x, $y, $z);
        if (!array_key_exists($z, static::$registry)) {
            static::$registry[$z] = [];
        }
        return static::$registry[$z][$new->getKey()] ?? (static::$registry[$z][$new->getKey()] = $new);
    }

    public static function xyzFlip(int $x, int $y, int $z): static
    {
        return static::xyz($x, (new static($x, $y, $z))->flipRow(), $z);
    }

    public static function key(int $key, int $zoom): TilePosition
    {
        $dimensions = GeometryHelper::getGridSize($zoom);
        $column = $dimensions ? floor($key / $dimensions) : 0;
        $row = $dimensions ? $key % $dimensions : 0;
        return static::xyz($column, $row, $zoom);
    }

    public static function keyFlip(int $key, int $zoom): TilePosition
    {
        $dimensions = GeometryHelper::getGridSize($zoom);
        $column = $dimensions ? floor($key / $dimensions) : 0;
        $row = $dimensions ? $key % $dimensions : 0;
        return static::xyzFlip($column, $row, $zoom);
    }

    public static function clearRegistry(): int
    {
        $count = array_reduce(static::$registry, fn(int $c, array $item) => $c + count($item), 0);
        static::$registry = [];
        return $count;
    }

    public function getMinPoint(): Point
    {
        return $this->minPoint ?? ($this->minPoint = Point::xy($this->getColumn() * $this->getTileWidth()
            - WebMercatorProjection::EARTH_RADIUS,
            $this->getRow() * $this->getTileWidth()
            - WebMercatorProjection::EARTH_RADIUS,
            WebMercatorProjection::SRID
        )->withSRID(WebMercatorProjection::SRID));
    }

    public function getMaxPoint(): Point
    {
        if ($this->maxPoint) {
            return $this->maxPoint;
        }
        $minPoint = $this->getMinPoint();
        return $this->maxPoint = Point::xy($minPoint->x() + $this->getTileWidth(), $minPoint->y() + $this->getTileWidth())
            ->withSRID(WebMercatorProjection::SRID);
    }
}
