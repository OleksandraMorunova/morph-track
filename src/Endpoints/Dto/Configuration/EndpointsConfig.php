<?php

namespace OM\MorphTrack\Endpoints\Dto\Configuration;

use OM\MorphTrack\Core\Facades\GlobalConfigFacade;
use OM\MorphTrack\GlobalConfig;

class EndpointsConfig
{
    public array $postFiltering = [];

    public string $prettyPrintUsed;

    public array $prettyPrintTypes;

    public GlobalConfig $globalConfig;

    public function __construct(public bool $includeNs = false)
    {
        $this->postFiltering = config('morph_track_config.route_pipelines.post_filtered', []);
        $this->prettyPrintUsed = config('morph_track_config.pretty_print.used', 'flat_verbose');
        $this->prettyPrintTypes = config('morph_track_config.pretty_print.types', []);
        $this->globalConfig = GlobalConfigFacade::instance();
    }

    public function getPrettyPrintClass(?string $resolver = null)
    {
        $resolver = $resolver ?? $this->prettyPrintUsed;

        return $this->prettyPrintTypes[$resolver]['resolver'] ?? null;
    }
}
