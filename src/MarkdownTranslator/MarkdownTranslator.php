<?php

namespace OM\MorphTrack\MarkdownTranslator;

use Illuminate\Translation\Translator;

class MarkdownTranslator
{
    public function __construct(
        protected Translator $translator,
        protected string $locale = 'en',
        protected bool $markdownFormatted = false,
    ) {}

    public function translate($key = null, $replace = [], $locale = null, string $prefix = ''): ?string
    {
        $prefix = $this->markdownFormatted ? $prefix : '';

        if (is_null($key)) {
            return $key;
        }

        return $prefix.trans($key, $replace, $locale);
    }
}
