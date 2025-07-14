<?php

namespace OM\MorphTrack\MarkdownTranslator;

use Illuminate\Translation\Translator;
use OM\MorphTrack\MarkdownSupport;

class MarkdownTranslator
{
    public function __construct(
        protected Translator $translator,
        protected string $locale = 'en',
        protected bool $markdownFormatted = false,
    ) {}

    public function translate($key = null, $replace = [], $locale = null, string $markdown = ''): ?string
    {
        if (is_null($key)) {
            return $key;
        }

        $trans = trans($key, $replace, $locale);
        return $this->format($markdown, $trans);
    }

    public function format(string $markdown, string $trans): string
    {
        $markdown = $this->markdownFormatted ? $markdown : '';

        if(in_array($markdown, [MarkdownSupport::HEADING_H1, MarkdownSupport::HEADING_H2, MarkdownSupport::HEADING_H3])) {
            return "$markdown $trans";
        }

        if(in_array($markdown, [MarkdownSupport::BOLD, MarkdownSupport::ITALIC, MarkdownSupport::CODE])) {
            return "$markdown $trans $markdown";
        }

        return "$trans";
    }
}
