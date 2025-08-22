<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations;

use Closure;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use OM\MorphTrack\Endpoints\Contracts\PipelineStepContract;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;
use OM\MorphTrack\Endpoints\Services\DocsSupport\Scramble\ScrambleHelper;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\EndpointPipelineContext;

class ProcessUsagesRoutes implements PipelineStepContract
{
    protected EndpointsConfig $config;

    public function __construct(protected ScrambleHelper $scrambleHelper) {}

    /**
     * @throws \ReflectionException
     */
    public function handle(EndpointPipelineContext $context, Closure $next): EndpointPipelineContext
    {
        $this->config = $context->getConfig();
        $this->scrambleHelper->config = $this->config;

        $this->scrambleHelper->scrambleSupport();

        $details = $context->getFiles();

        $routes = RouteFacade::getRoutes();
        foreach ($routes as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure') {
                continue;
            }

            [$controller, $method] = Str::contains($action, '@') ?
                explode('@', $action) :
                [
                    $action,
                    '__invoke',
                ];
            $this->fillUsageEntry($details, $route, $controller, $method);
        }

        $context->setFiles($details);

        return $next($context);
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

                [$newUri, $summary] = $this->scrambleHelper->scrambleBuildUri($route, $method);
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
}
