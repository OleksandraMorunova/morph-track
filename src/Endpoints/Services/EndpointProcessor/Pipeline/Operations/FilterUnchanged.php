<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations;

use Closure;
use OM\MorphTrack\Endpoints\Contracts\PipelineStepContract;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\EndpointProcessorHelper;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\EndpointPipelineContext;

class FilterUnchanged implements PipelineStepContract
{
    protected string $localization = 'en';

    public function handle(EndpointPipelineContext $context, Closure $next): EndpointPipelineContext
    {
        $config = $context->getConfig();
        $this->localization = $config->globalConfig->localization;

        $details = $context->getFiles();
        $output = [];

        foreach ($details as $entry) {
            $this->formatOutput($config, $entry, $output);
        }

        $filtered = $this->formatFinalOutput($output, $context->getConfig()->includeNs);
        $context->setFiltered($filtered);

        return $next($context);
    }

    protected function formatOutput(EndpointsConfig $config, array $entry, array &$output): void
    {
        if (empty($entry['usedIn'])) {
            return;
        }

        foreach ($entry['usedIn'] as $usage) {
            $routeKey = $this->formatHeader($config, $usage);

            if (! isset($output[$routeKey])) {
                $output[$routeKey] = [];
            }

            $type = $entry['type'];
            $output[$routeKey][$type][] = [
                'namespace' => $entry['namespace'],
                'status' => $entry['status'],
                'original_uri' => $usage['original_uri'],
            ];
        }
    }

    protected function formatHeader(EndpointsConfig $config, array $usage): string
    {
        $uri = $usage['uri'];

        if ($config->globalConfig->markdownFormatted && $config->useScramble) {
            $summary = $usage['summary'];
            if (! $summary) {
                $parts = explode('/', $uri);
                $summary = end($parts);
            }

            return "{$usage['method']} [$summary]($uri)";
        }

        return "{$usage['method']} $uri";
    }

    protected function formatFinalOutput(array $output, bool $printNamespace = false): array
    {
        $lines = [];

        foreach ($output as $route => $types) {
            $this->formatRouteTypes($types, $route, $printNamespace, $lines);
        }

        return $lines;
    }

    protected function formatRouteTypes(array $types, $route, bool $printNamespace, array &$lines): void
    {
        foreach ([EndpointProcessorHelper::REQUEST, EndpointProcessorHelper::RESOURCE] as $type) {
            if (! isset($types[$type])) {
                continue;
            }

            foreach ($types[$type] as $item) {
                $status = $item['status'] ?? __(key: 'analyze-endpoints::no_changes', locale: $this->localization);

                $lines["-  $route"]['body'][] = $printNamespace
                    ? "     -  $type {$item['namespace']} – $status"
                    : "     -  $type – $status";
                $lines["-  $route"]['original_uri'] = $item['original_uri'];
            }
        }
    }
}
