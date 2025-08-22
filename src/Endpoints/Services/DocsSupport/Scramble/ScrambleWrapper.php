<?php

namespace OM\MorphTrack\Endpoints\Services\DocsSupport\Scramble;

class ScrambleWrapper
{
    public static function get(): ?array
    {
        if (! class_exists(\Dedoc\Scramble\Generator::class)) {
            return null;
        }

        $class = app(\Dedoc\Scramble\Generator::class);

        return self::make($class);
    }

    public static function make($scrambleGenerator): ?array
    {
        if (! class_exists(\Dedoc\Scramble\Scramble::class)) {
            return null;
        }

        $config = \Dedoc\Scramble\Scramble::getGeneratorConfig('default');

        return $scrambleGenerator($config);
    }
}
