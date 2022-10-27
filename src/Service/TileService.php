<?php

namespace HeyMoon\MVTTools\Service;

use Brick\Geo\Curve;
use Brick\Geo\Engine\GeometryEngine;
use Brick\Geo\Exception\CoordinateSystemException;
use Brick\Geo\Exception\EmptyGeometryException;
use Brick\Geo\Exception\GeometryEngineException;
use Brick\Geo\Exception\InvalidGeometryException;
use Brick\Geo\Exception\NoSuchGeometryException;
use Brick\Geo\Exception\UnexpectedGeometryException;
use Brick\Geo\Geometry;
use Brick\Geo\GeometryCollection;
use Brick\Geo\LineString;
use Brick\Geo\Point;
use Brick\Geo\Polygon;
use Brick\Geo\Surface;
use ErrorException;
use Exception;
use HeyMoon\MVTTools\Factory\GeometryCollectionFactory;
use HeyMoon\MVTTools\Factory\SourceFactory;
use HeyMoon\MVTTools\Helper\GeometryHelper;
use HeyMoon\MVTTools\Entity\Layer;
use HeyMoon\MVTTools\Entity\Shape;
use HeyMoon\MVTTools\Entity\TilePosition;
use Vector_tile\Tile;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TileService
{
    public const DEFAULT_EXTENT = 4096;

    /**
     * List of possible commands
     * https://github.com/mapbox/vector-tile-spec/tree/master/2.1#433-command-types
     */
    public const MOVE_TO = 1;
    public const LINE_TO = 2;
    public const CLOSE_PATH = 7;

    private array $valuesCache = [];

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(
        private readonly GeometryEngine $geometryEngine,
        private readonly SourceFactory $sourceFactory,
        private readonly GeometryCollectionFactory $geometryCollectionFactory,
        private readonly float  $minTolerance = 0,
        private readonly bool $flip = true,
    ) {}

    /**
     * @param Shape[] $shapes
     * @param TilePosition $position
     * @param int $extent
     * @param float|null $buffer
     * @return Tile
     * @throws CoordinateSystemException
     * @throws EmptyGeometryException
     * @throws GeometryEngineException
     * @throws UnexpectedGeometryException
     * @throws InvalidGeometryException
     * @throws NoSuchGeometryException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getTileMVT(
        array $shapes,
        TilePosition $position,
        int $extent = self::DEFAULT_EXTENT,
        ?float $buffer = null
    ): Tile
    {
        $shapeLayers = [];
        $byLayer = [];
        foreach ($shapes as $item) {
            $name = $item->getLayer()->getName();
            if (!array_key_exists($name, $shapeLayers)) {
                $shapeLayers[$name] = $item->getLayer();
                $byLayer[$name] = [];
            }
            $byLayer[$name][] = $item;
        }
        $this->valuesCache = [];
        $keys = [];
        /** @var Tile\Value[] $values */
        $values = [];
        $features = [];
        $border = $position->getBorder();
        list($minPoint, $maxPoint) = GeometryHelper::getBounds($border);
        $width = $maxPoint->x() - $minPoint->x();
        $height = $maxPoint->y() - $minPoint->y();
        $xScale = $extent / $width;
        $yScale = $extent / $height;
        $bufferedBounds = $buffer ? $this->geometryEngine->buffer($border, $buffer) : $border;
        $tolerance = $width / $extent;
        $layers = [];
        foreach ($byLayer as $name => $data) {
            foreach ($tolerance > $this->minTolerance ?
                         $this->simplify($shapeLayers[$name], $data, $tolerance) :
                         $data as $item) {
                $shape = $item->getGeometry();
                if (!$this->geometryEngine->contains($bufferedBounds, $shape)) {
                    $intersection = $this->geometryEngine->intersection($shape, $bufferedBounds);
                    $geometries = $intersection instanceof GeometryCollection ? $intersection->geometries() : [$intersection];
                } else {
                    $geometries = [$shape];
                }
                foreach ($geometries as $geometry) {
                    $feature = new Tile\Feature();
                    $feature->setId($item->getId());
                    $closePath = false;
                    $points = [];
                    try {
                        if ($geometry instanceof Curve) {
                            $previous = $geometry->startPoint();
                            $points = method_exists($geometry, 'points') ? $geometry->points() :
                                [$previous, $geometry->endPoint()];
                            if ($geometry instanceof LineString) {
                                $feature->setType(Tile\GeomType::LINESTRING);
                            } else {
                                $feature->setType(Tile\GeomType::UNKNOWN);
                            }
                        } elseif ($geometry instanceof Point) {
                            $feature->setType(Tile\GeomType::POINT);
                            $previous = $geometry;
                        } elseif ($geometry instanceof Polygon) {
                            $feature->setType(Tile\GeomType::POLYGON);
                            $closePath = true;
                            $points = $geometry->exteriorRing()->points();
                            $previous = $geometry->exteriorRing()->startPoint();
                        } else {
                            continue;
                        }
                    } catch (EmptyGeometryException) {
                        continue;
                    }
                    $parameters = $item->getParameters();
                    $new = array_keys($parameters);
                    foreach ($new as $key) {
                        if (!in_array($key, $keys)) {
                            $keys[] = $key;
                        }
                    }
                    $feature->setTags($this->addValues($parameters, $keys, $values));
                    $tileGeometry = [];
                    $tileGeometry[] = $this->encodeCommand(static::MOVE_TO);
                    $newX = (int)round(($previous->x() - $minPoint->x()) * $xScale);
                    if ($this->flip) {
                        $newY = (int)round(($maxPoint->y() - $previous->y()) * $yScale);
                    } else {
                        $newY = (int)round(($previous->y() - $minPoint->y()) * $yScale);
                    }
                    $tileGeometry[] = $this->encodeValue($newX);
                    $tileGeometry[] = $this->encodeValue($newY);
                    $lineTo = [];
                    $lineToCount = 0;
                    foreach (array_slice($points, 1) as $point) {
                        $newX = (int)round(($point->x() - $previous->x()) * $xScale);
                        $newY = (int)round(($point->y() - $previous->y()) * $yScale);
                        if ($newX === 0 && $newY === 0) {
                            continue;
                        }
                        if ($this->flip) {
                            $newY *= -1;
                        }
                        $lineTo[] = $this->encodeValue($newX);
                        $lineTo[] = $this->encodeValue($newY);
                        $previous = $point;
                        $lineToCount++;
                    }
                    $tileGeometry[] = $this->encodeCommand(static::LINE_TO, $lineToCount);
                    if ($closePath) {
                        $lineTo[] = $this->encodeCommand(static::CLOSE_PATH);
                    }
                    $feature->setGeometry(array_merge($tileGeometry, $lineTo));
                    $features[] = $feature;
                }
            }
            $layer = $this->createLayer($name, $extent);
            $layer->setKeys($keys);
            $layer->setValues($values);
            $layer->setFeatures($features);
            $layers[] = $layer;
        }
        $tile = new Tile();
        $tile->setLayers($layers);
        $this->valuesCache = [];
        return $tile;
    }

    public function getExtent(Tile $tile): int
    {
        $extent = 0;
        foreach ($tile->getLayers() as $layer) {
            $extent = max($extent, $layer->getExtent());
        }
        return $extent ?: static::DEFAULT_EXTENT;
    }

    public function mergeLayers(Tile $tile): Tile
    {
        $this->valuesCache = [];
        $oldLayers = [];
        $extent = $this->getExtent($tile);
        foreach ($tile->getLayers() as $oldLayer) {
            /** @var Tile\Layer $oldLayer */
            if (!array_key_exists($oldLayer->getName(), $oldLayers)) {
                $oldLayers[$oldLayer->getName()] = [];
            }
            $oldLayers[$oldLayer->getName()][] = $oldLayer;
        }
        $result = [];
        foreach ($oldLayers as $name => $layers) {
            $newLayer = $this->createLayer($name, $extent);
            $keys = [];
            $values = [];
            $features = [];
            $hashes = [];
            foreach ($layers as $layer) {
                /** @var Tile\Layer $layer */
                foreach ($layer->getFeatures() as $feature) {
                    /** @var Tile\Feature $feature */
                    $geometry = [];
                    for ($i = 0; $i < $feature->getGeometry()->count(); $i++) {
                        try {
                            if (!$feature->getGeometry()->offsetExists($i)) {
                                continue;
                            }
                            $geometry[] = $feature->getGeometry()->offsetGet($i);
                        } catch (ErrorException) {
                            continue;
                        }
                    }
                    $hash = md5(implode('+', $geometry));
                    if (in_array($hash, $hashes, true)) {
                        continue;
                    }
                    $hashes[] = $hash;
                    $newFeature = new Tile\Feature();
                    $parameters = $this->getValues($layer, $feature);
                    $tags = $this->addValues($parameters, $keys, $values);
                    $newFeature->setTags($tags);
                    $newFeature->setId($feature->getId());
                    $newFeature->setType($feature->getType());
                    $newFeature->setGeometry($feature->getGeometry());
                    $features[] = $newFeature;
                }
            }
            $newLayer->setKeys($keys);
            $newLayer->setValues($values);
            $newLayer->setFeatures($features);
            $result[] = $newLayer;
        }
        $newTile = new Tile();
        $newTile->setLayers($result);
        $this->valuesCache = [];
        return $newTile;
    }

    public function createLayer(string $name, int $extent = self::DEFAULT_EXTENT): Tile\Layer
    {
        $layer = new Tile\Layer();
        $layer->setName($name);
        $layer->setExtent($extent);
        $layer->setVersion(2);
        return $layer;
    }

    public function getValues(Tile\Layer $layer, Tile\Feature $feature): array
    {
        $result = [];
        for ($i = 0; $i < count($feature->getTags()) / 2; $i++) {
            $pos = $i * 2;
            $key = $feature->getTags()[$pos];
            $value = $feature->getTags()[$pos + 1];
            /** @var Tile\Value $object */
            $object = $layer->getValues()[$value];
            $result[$layer->getKeys()[$key]] = $object->getStringValue() ?: $object->getUintValue();
        }
        return $result;
    }

    /**
     * https://github.com/mapbox/vector-tile-spec/tree/master/2.1#431-command-integers
     *
     * @param int $id
     * @param int $count
     * @return int
     */
    public function encodeCommand(int $id, int $count = 1): int
    {
        return ($id & 0x7) | ($count << 3);
    }

    /**
     * Returns: [0]: command, [1]: count
     *
     * @param int $command
     * @return int[]
     */
    public function decodeCommand(int $command): array
    {
        return [$command & 0x7, $command >> 3];
    }

    /**
     * https://github.com/mapbox/vector-tile-spec/tree/master/2.1#432-parameter-integers
     *
     * @param int $value
     * @return int
     */
    public function encodeValue(int $value): int
    {
        return ($value << 1) ^ ($value >> 31);
    }

    public function decodeValue(int $value): int
    {
        return (($value >> 1) ^ (-($value & 1)));
    }

    /**
     * @param Layer $layer
     * @param Shape[] $data
     * @param float $tolerance
     * @return Shape[]
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws GeometryEngineException
     */
    protected function simplify(Layer $layer, array $data, float $tolerance): array
    {
        /** @var Geometry[][] $shapeByParameters */
        $shapeByParameters = [];
        $parameters = [];
        $idsByType = [];
        /** @var Shape[] $parents */
        foreach ($data as $shape) {
            if (!$shape instanceof Shape) {
                continue;
            }
            $geometry = $shape->getGeometry();
            $values = $shape->getParameters();
            $key = http_build_query($values);
            if (!array_key_exists($key, $parameters)) {
                $parameters[$key] = $values;
                $shapeByParameters[$key] = [];
                $idsByType[$key] = [];
            }
            foreach ($geometry instanceof GeometryCollection ? $geometry->geometries() : [$geometry] as $item) {
                if (!array_key_exists($item->geometryTypeBinary(), $idsByType[$key])) {
                    $idsByType[$key][$item->geometryTypeBinary()] = [];
                }
                $shapeByParameters[$key][] = $item;
                $idsByType[$key][$item->geometryTypeBinary()][] = $shape->getId();
            }
        }
        $result = $this->sourceFactory->create()->getLayer($layer->getName());
        foreach ($shapeByParameters as $key => $shapes) {
            $currentParameters = $parameters[$key];
            $shapesByTypes = [];
            foreach ($shapes as $shape) {
                if (!array_key_exists($shape->geometryTypeBinary(), $shapesByTypes)) {
                    $shapesByTypes[$shape->geometryTypeBinary()] = [];
                }
                $shapesByTypes[$shape->geometryTypeBinary()][] = $shape;
            }
            $simplified = [];
            foreach ($shapesByTypes as $type => $items) {
                if (!array_key_exists($type, $simplified)) {
                    $simplified[$type] = [];
                }
                $simplified[$type][] = $this->geometryEngine->simplify(
                    count($items) ? $this->geometryCollectionFactory->get($items) : array_shift($items), $tolerance
                );
            }
            foreach ($simplified as $type => $byType) {
                foreach ($byType as $collection) {
                    foreach ($collection instanceof GeometryCollection ? $collection->geometries() : [$collection]
                             as $item) {
                        $id = array_shift($idsByType[$key][$type]);
                        if ($item instanceof Curve) {
                            if ($this->geometryEngine->length($item) < $tolerance) {
                                continue;
                            }
                        } elseif ($item instanceof Surface) {
                            if ($this->geometryEngine->area($item) < $tolerance) {
                                continue;
                            }
                        }
                        $result->add(
                            $item,
                            $currentParameters,
                            0,
                            $id
                        );
                    }
                }
            }
        }
        return $result->getShapes();
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function addValues(array $parameters, array &$keys, array &$values): array
    {
        $new = array_keys($parameters);
        foreach ($new as $key) {
            if (!in_array($key, $keys)) {
                $keys[] = $key;
            }
        }
        $tags = [];
        foreach ($keys as $id => $key) {
            $value = $parameters[$key] ?? null;
            if (is_null($value)) {
                continue;
            }
            foreach ($values as $tag => $existing) {
                if (($value == ($this->valuesCache[$key] ?? null)) ||
                    in_array($value, [$existing->getStringValue(), $existing->getUintValue()], true)) {
                    $tags[] = $id;
                    $tags[] = $tag;
                    continue 2;
                }
            }
            $protoValue = new Tile\Value();
            try {
                if (is_string($value)) {
                    $protoValue->setStringValue($value);
                } elseif (is_int($value)) {
                    try {
                        $protoValue->setUintValue($value);
                    } catch (Exception) {
                        try {
                            $protoValue->setSintValue($value);
                        } catch (Exception) {
                            $protoValue->setIntValue($value);
                        }
                    }
                } elseif (is_float($value)) {
                    try {
                        $protoValue->setFloatValue($value);
                    } catch (Exception) {
                        $protoValue->setDoubleValue($value);
                    }
                } elseif (is_bool($value)) {
                    $protoValue->setBoolValue($value);
                } else {
                    continue;
                }
            } catch (Exception) {
                continue;
            }
            $values[] = $protoValue;
            $this->valuesCache[] = $value;
            $tags[] = $id;
            $tags[] = array_key_last($values);
        }
        return $tags;
    }
}
