<?php

namespace OM\MorphTrack\Core\Service\DocsSupport;

use Illuminate\Routing\Route;

class NullDocsHelper extends DocsHelper
{
    public function prepare(): void {}

    public function buildUri(Route $route, string $method): array
    {
        return [$route->uri(), null];
    }

    public function formatHeader(array $usage, string $uri): ?string
    {
        return null;
    }

    public function __call(string $name, array $arguments)
    {
        return null;
    }
}