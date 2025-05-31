<?php

namespace HeyMoon\VectorTileDataProvider\Service;

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
use Brick\Geo\MultiPoint;
use Brick\Geo\Point;
use Brick\Geo\Polygon;
use ErrorException;
use Exception;
use HeyMoon\VectorTileDataProvider\Contract\GeometryCollectionFactoryInterface;
use HeyMoon\VectorTileDataProvider\Contract\SourceFactoryInterface;
use HeyMoon\VectorTileDataProvider\Contract\TileServiceInterface;
use HeyMoon\VectorTileDataProvider\Entity\AbstractLayer;
use HeyMoon\VectorTileDataProvider\Entity\Feature;
use HeyMoon\VectorTileDataProvider\Entity\TilePosition;
use HeyMoon\VectorTileDataProvider\Spatial\WebMercatorProjection;
use Vector_tile\Tile;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class TileService implements TileServiceInterface
{
    /**
     * List of possible commands
     * https://github.com/mapbox/vector-tile-spec/tree/master/2.1#433-command-types
     */
    public const MOVE_TO = 1;
    public const LINE_TO = 2;
    public const CLOSE_PATH = 7;

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(
        private readonly GeometryEngine $geometryEngine,
        private readonly SourceFactoryInterface $sourceFactory,
        private readonly GeometryCollectionFactoryInterface $geometryCollectionFactory,
        private readonly float  $minTolerance = 0,
        private readonly bool $flip = true,
    ) {}

    /**
     * @param Feature[] $shapes
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
        $minPoint = $position->getMinPoint();
        $maxPoint = $position->getMaxPoint();
        $border = $this->geometryEngine->envelope(MultiPoint::of($position->getMinPoint(), $position->getMaxPoint()));
        $width = $position->getTileWidth();
        $scale = $extent / $width;
        $bufferedBounds = $buffer ? $this->geometryEngine->buffer($border, $buffer) : $border;
        $tolerance = $width / $extent;
        $layers = [];
        foreach ($byLayer as $name => $data) {
            $features = [];
            $keys = [];
            /** @var Tile\Value[] $values */
            $values = [];
            $valuesCache = [];
            foreach ($tolerance > $this->minTolerance ?
                         $this->simplify($shapeLayers[$name], $data, $tolerance) :
                         $data as $item) {
                $shapes = $item->getGeometry();
                foreach ($shapes instanceof GeometryCollection ? $shapes->geometries() : [$shapes] as $shape) {
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
                        $feature->setTags($this->addValues($item->getParameters(),
                            $keys, $values, $valuesCache));
                        $tileGeometry = [];
                        $tileGeometry[] = $this->encodeCommand(static::MOVE_TO);
                        $newX = (int)round(($previous->x() - $minPoint->x()) * $scale);
                        if ($this->flip) {
                            $newY = (int)round(($maxPoint->y() - $previous->y()) * $scale);
                        } else {
                            $newY = (int)round(($previous->y() - $minPoint->y()) * $scale);
                        }
                        $tileGeometry[] = $this->encodeValue($newX);
                        $tileGeometry[] = $this->encodeValue($newY);
                        $lineTo = [];
                        $lineToCount = 0;
                        foreach (array_slice($points, 1) as $point) {
                            $newX = (int)round(($point->x() - $previous->x()) * $scale);
                            $newY = (int)round(($point->y() - $previous->y()) * $scale);
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
            }
            $layer = $this->createLayer($name, $extent);
            $layer->setKeys($keys);
            $layer->setValues($values);
            $layer->setFeatures($features);
            $layers[] = $layer;
        }
        $tile = new Tile();
        $tile->setLayers($layers);
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
            $valuesCache = [];
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
                    $tags = $this->addValues($parameters, $keys, $values, $valuesCache);
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
            $result[$layer->getKeys()[$key]] = $this->getValue($object);
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
     * @param AbstractLayer $layer
     * @param Feature[] $data
     * @param float $tolerance
     * @return Feature[]
     * @throws CoordinateSystemException
     * @throws UnexpectedGeometryException
     * @throws GeometryEngineException
     */
    protected function simplify(AbstractLayer $layer, array $data, float $tolerance): array
    {
        /** @var Geometry[][] $shapeByParameters */
        $shapeByParameters = [];
        $parameters = [];
        $idsByType = [];
        /** @var Feature[] $parents */
        foreach ($data as $shape) {
            if (!$shape instanceof Feature) {
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
                try {
                    $simplified[$type] = $this->geometryEngine->simplify(
                        count($items) > 1 ? $this->geometryCollectionFactory->get($items) : array_shift($items), $tolerance
                    );
                } catch (GeometryEngineException) {
                    $simplified[$type] = $this->geometryEngine->simplify($this->geometryEngine->buffer(
                        count($items) > 1 ? $this->geometryCollectionFactory->get($items) :
                            array_shift($items), $tolerance
                    ), $tolerance);
                }
            }
            foreach ($simplified as $type => $collection) {
                foreach ($collection instanceof GeometryCollection ? $collection->geometries() : [$collection]
                         as $item) {
                    $id = array_shift($idsByType[$key][$type]);
                    if ($item instanceof Curve) {
                        if ($this->geometryEngine->length($item) < $tolerance) {
                            continue;
                        }
                    }
                    $result->add(
                        $item->withSRID(WebMercatorProjection::SRID),
                        $currentParameters,
                        0,
                        $id
                    );
                }
            }
        }
        $features = $result->getFeatures();
        usort($features, fn(Feature $a, Feature $b) => $b->getId() - $a->getId());
        return $features;
    }

    private function getValue(Tile\Value $object): bool|int|float|string|null
    {
        foreach (['Bool', 'Int', 'Uint', 'Sint', 'Float', 'Double', 'String'] as $type) {
            $hasValueMethod = "has{$type}Value";
            if (!$object->$hasValueMethod()) {
                continue;
            }
            $getValueMethod = "get{$type}Value";
            return $object->$getValueMethod();
        }
        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function addValues(array $parameters, array &$keys, array &$values, array &$valuesCache): array
    {
        /** @var Tile\Value[] $values */
        $new = array_keys($parameters);
        foreach ($new as $key) {
            if (!in_array($key, $keys, true) && !is_null($parameters[$key])) {
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
                if (($value === ($valuesCache[$key] ?? null)) ||
                    ($value === $this->getValue($existing))) {
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
            $valuesCache[] = $value;
            $tags[] = $id;
            $tags[] = array_key_last($values);
        }
        return $tags;
    }
}
