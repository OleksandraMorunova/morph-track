<?php

namespace OM\MorphTrack\Core\Service\DocsSupport;

use Illuminate\Routing\Route;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;

abstract class DocsHelper
{
    public EndpointsConfig $config;

    abstract public function prepare(): void;

    abstract public function buildUri(Route $route, string $method): array;

    public function formatHeader(array $usage, string $uri): ?string
    {
        if (! $this->config->globalConfig->markdownFormatted) {
            return null;
        }

        $summary = $usage['summary'];
        if (! $summary) {
            $parts = explode('/', $uri);
            $summary = end($parts);
        }

        return "{$usage['method']} [$summary]($uri)";
    }
}
