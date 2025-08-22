<?php

namespace OM\MorphTrack;

class GlobalConfig
{
    public string $localization;
    public string $workingDir;
    public bool $markdownFormatted = true;

    public function __construct(public bool $includeNs = false)
    {
        $this->localization = config('morph_track_config.field_change_locale') ?? 'en';
        $this->markdownFormatted = config('morph_track_config.markdown_formatted');
        $this->workingDir = config('morph_track_config.working_dir', '/var/www/html');
    }
}
