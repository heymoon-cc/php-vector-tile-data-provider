<?php

namespace HeyMoon\VectorTileDataProvider\Contract;

interface SourceFactoryInterface
{
    public function create(): SourceInterface;
}
