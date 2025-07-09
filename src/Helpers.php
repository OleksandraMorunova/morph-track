<?php

use OM\MorphTrack\MarkdownTranslator\Facades\MarkdownTranslator;

if (! function_exists('__m')) {
    function __m(string $key, array $replace = [], $locale = null, string $prefix = ''): string
    {
        return MarkdownTranslator::translate($key, $replace, $locale, $prefix);
    }
}
