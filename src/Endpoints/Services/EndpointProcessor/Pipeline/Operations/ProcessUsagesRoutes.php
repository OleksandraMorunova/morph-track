<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use OM\MorphTrack\Endpoints\Contracts\PipelineStepContract;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\EndpointPipelineContext;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\ScrambleWrapper;

class ProcessUsagesRoutes implements PipelineStepContract
{
    protected ?array $openApiPaths = null;

    protected ?string $scrambleServerUri = null;

    protected EndpointsConfig $config;

    /**
     * @throws \ReflectionException
     */
    public function handle(EndpointPipelineContext $context, Closure $next): EndpointPipelineContext
    {
        $this->config = $context->getConfig();
        $this->scrambleSupport();

        $details = $context->getFiles();

        $routes = RouteFacade::getRoutes();
        foreach ($routes as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure' || ! Str::contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action);
            $this->fillUsageEntry($details, $route, $controller, $method);
        }

        $context->setFiles($details);

        return $next($context);
    }

    protected function scrambleSupport(): void
    {
        $generator = ScrambleWrapper::get();

        if (! $this->config->useScramble || ! is_array($generator)) {
            return;
        }

        $this->scrambleServerBuildUri($generator);

        $this->openApiPaths = $generator['paths'] ?? null;
    }

    /**
     * @throws \ReflectionException
     */
    protected function fillUsageEntry(array &$details, $route, string $controller, string $methodName): void
    {
        $rc = new \ReflectionMethod($controller, $methodName);
        $file = $rc->getFileName();
        $lines = file($file);
        $body = implode('', array_slice($lines, $rc->getStartLine() - 1, $rc->getEndLine() - $rc->getStartLine() + 1));

        foreach ($details as $namespace => &$entry) {
            $class = class_basename($namespace);
            if (
                preg_match('/\b'.preg_quote($class, '/').'\b/', $body) ||
                preg_match('/\b'.preg_quote($namespace, '/').'\b/', $body)
            ) {
                $method = $route->methods()[0];

                [$newUri, $summary] = $this->scrambleBuildUri($route, $method);
                $entry['usedIn'][] = [
                    'method' => $method,
                    'original_uri' => $route->uri,
                    'summary' => $summary,
                    'uri' => $newUri,
                    'ctrl' => $controller,
                    'methodName' => $methodName,
                ];
            }
        }
    }

    protected function scrambleServerBuildUri(array $generator): void
    {
        $serverConfig = $this->config->scrambleServerConfig;
        $scrambleServer = $generator['servers'];

        if ($serverConfig) {
            $this->scrambleServerUri = collect($scrambleServer)
                ->firstWhere('description', $serverConfig)['url'] ?? $scrambleServer[0];
        } else {
            $this->scrambleServerUri = $scrambleServer[0];
        }
        $this->scrambleServerUri = preg_replace('#/api#', '', $this->scrambleServerUri);

        $this->scrambleServerUri = "$this->scrambleServerUri/docs/api#/operations/";
    }

    protected function scrambleBuildUri(Route $route, string $method): array
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
}
