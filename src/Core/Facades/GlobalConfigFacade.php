<?php

namespace OM\MorphTrack\Core\Facades;

use Illuminate\Support\Facades\Facade;
use OM\MorphTrack\GlobalConfig;

class GlobalConfigFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GlobalConfig::class;
    }

    public static function instance(): GlobalConfig
    {
        /** @var GlobalConfig $cfg */
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }
}