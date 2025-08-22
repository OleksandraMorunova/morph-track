<?php

namespace OM\MorphTrack\Core\Service\DocsSupport\Scramble;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;

class ScrambleHelper
{
    public EndpointsConfig $config;
    protected ?array $openApiPaths = null;
    protected ?string $scrambleServerUri = null;

    public function scrambleSupport(): void
    {
        $generator = ScrambleWrapper::get();

        if (! $this->config->useScramble || ! is_array($generator)) {
            return;
        }

        $this->scrambleServerBuildUri($generator);

        $this->openApiPaths = $generator['paths'] ?? null;
    }

    public function scrambleServerBuildUri(array $generator): void
    {
        $serverConfig = $this->config->scrambleServerConfig;
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

    public function scrambleBuildUri(Route $route, string $method): array
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

    public function formatHeader(array $usage, string $uri): ?string
    {
        if (! $this->config->globalConfig->markdownFormatted || ! $this->config->useScramble) {
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