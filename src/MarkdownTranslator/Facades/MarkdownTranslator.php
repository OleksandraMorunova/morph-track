<?php

namespace OM\MorphTrack\MarkdownTranslator\Facades;

use Illuminate\Support\Facades\Facade;

class MarkdownTranslator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'markdown-translator';
    }
}
