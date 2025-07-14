<?php

use OM\MorphTrack\MarkdownTranslator\Facades\MarkdownTranslator;

if (! function_exists('__m')) {
    function __m(string $key, array $replace = [], $locale = null, string $markdown = ''): string
    {
        return MarkdownTranslator::translate($key, $replace, $locale, $markdown);
    }

    function __f(string $markdown, string $text): string
    {
        return MarkdownTranslator::format($markdown, $text);
    }
}
