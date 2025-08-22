<?php

namespace OM\MorphTrack\Core\Service\DocsSupport\Scramble;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use OM\MorphTrack\Core\Service\DocsSupport\DocsHelper;

class ScrambleHelper extends DocsHelper
{
    protected ?array $openApiPaths = null;

    protected ?string $scrambleServerUri = null;

    public function prepare(): void
    {
        $generator = ScrambleWrapper::get();

        if (! is_array($generator)) {
            return;
        }

        $this->serverBuildUri($generator);

        $this->openApiPaths = $generator['paths'] ?? null;
    }

    public function buildUri(Route $route, string $method): array
    {
        $uri = $route->uri();

        if (! $this->openApiPaths) {
            return [$uri, null];
        }

        $method = strtolower($method);
        $normalizedUri = preg_replace('#^api#', '', $uri);

        $findMethod = $this->openApiPaths[$normalizedUri][$method] ?? null;
        $operationId = $findMethod['operationId'] ?? null;

        if (! $operationId) {
            $normalizedUri = preg_replace_callback('/\{(\w+)\}/', fn ($matches) => '{'.Str::camel($matches[1]).'}', $normalizedUri);
            $findMethod = $this->openApiPaths[$normalizedUri][$method] ?? null;
            $operationId = $findMethod['operationId'] ?? null;
        }

        $newUri = $operationId
            ? $this->scrambleServerUri.$operationId
            : $this->scrambleServerUri.$uri;

        $summary = $findMethod['summary'] ?? $findMethod['operationId'];

        return [$newUri, $summary];
    }

    protected function serverBuildUri(array $generator): void
    {
        $serverConfig = config('morph_track_config.docs_support.scramble.server', 'Live');

        $scrambleServer = $generator['servers'];

        if ($serverConfig) {
            $this->scrambleServerUri = collect($scrambleServer)
                ->firstWhere('description', $serverConfig)['url'] ?? $scrambleServer[0]['url'];
        } else {
            $this->scrambleServerUri = $scrambleServer[0];
        }
        $this->scrambleServerUri = preg_replace('#/api#', '', $this->scrambleServerUri);

        $this->scrambleServerUri = "$this->scrambleServerUri/docs/api#/operations/";
    }
}
