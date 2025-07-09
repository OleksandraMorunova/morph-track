<?php

namespace OM\MorphTrack\Endpoints\Services\Formatters;

use Illuminate\Support\Str;
use OM\MorphTrack\Endpoints\Core\AbstractFormatter;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;

class GroupByPrefixFormatter extends AbstractFormatter
{
    protected string $defaultGroup;

    protected array $groups;

    public function __construct(public EndpointsConfig $config)
    {
        parent::__construct($config);

        $this->groups = config('morph_track_config.pretty_print.types.group_by_prefix.route_prefix_groups');
        $this->defaultGroup = config('morph_track_config.pretty_print.types.group_by_prefix.default_route_prefix');
    }

    public function handle(array $routes): string
    {
        $grouped = [];

        foreach ($routes as $route => $items) {
            $matched = false;

            if (preg_match('#^(api/[^/]+/)#', $items['original_uri'], $matches)) {
                $uri = $matches[1];

                foreach ($this->groups as $label => $prefix) {
                    if (Str::startsWith($uri, $prefix)) {
                        $grouped[$label][$route] = $items['body'];
                        $matched = true;
                        break;
                    }
                }
            }

            if (! $matched) {
                $grouped[$this->defaultGroup][$route] = $items['body'];
            }
        }

        return $this->formatGroupedRoutes($grouped);
    }

    protected function formatGroupedRoutes(array $grouped): string
    {
        $output = [];

        foreach ($grouped as $label => $routes) {
            $output[] = "$label:";

            foreach ($routes as $route => $details) {
                $output[] = $route;

                foreach ($details as $line) {
                    $output[] = $line;
                }
            }

            $output[] = '';
        }

        return implode(PHP_EOL, $output);
    }
}
