<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations;

use Closure;
use Illuminate\Support\Str;
use OM\MorphTrack\Endpoints\Contracts\PipelineStepContract;
use OM\MorphTrack\Endpoints\Dto\Parameters\EndpointParameters;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\EndpointProcessorHelper;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\EndpointPipelineContext;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\GitHelper;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\RequestService;
use OM\MorphTrack\MarkdownSupport;
use Symfony\Component\Process\Process;

class ProcessStatusFiles implements PipelineStepContract
{
    protected const PATTERN_REQUEST_FIELD = "/^[\+\-][^:]*['\"]([A-Za-z_][A-Za-z0-9_]*)['\"]\s*=>/";

    protected const PATTERN_RESOURCE_FIELD = "/^[\+\-]\s*['\"]([A-Za-z0-9_\.]+)['\"]\s*=>/";

    protected string $localization;

    protected string $from;

    protected string $to;

    protected RequestService $requestService;

    public function handle(EndpointPipelineContext $context, Closure $next): EndpointPipelineContext
    {
        $this->localization = $context->getConfig()->globalConfig->localization;
        $this->requestService = new RequestService($this->localization);
        $params = $context->getParams();

        [$this->from, $this->to] = [$params->from, $params->to];

        $details = [];

        foreach ($context->getFiles() as $file) {
            $this->processDetails($file, $params, $details);
        }

        $context->setFiles($details);

        return $next($context);
    }

    protected function processDetails(string $file, EndpointParameters $params, array &$details): void
    {
        $base = pathinfo($file, PATHINFO_FILENAME);
        $namespace = EndpointProcessorHelper::pathToNamespace($file);

        $status = EndpointProcessorHelper::gitFileStatus($params, $file);
        $type = Str::contains($file, 'app/Http/Resources/') ? EndpointProcessorHelper::RESOURCE : EndpointProcessorHelper::REQUEST;
        $lineStatus = $this->getLabels($status, $file, $type, $namespace);

        if (! $lineStatus && $type == EndpointProcessorHelper::REQUEST) {
            return;
        }

        $details[$base] = [
            'status' => $lineStatus,
            'namespace' => $namespace,
            'type' => $type,
            'usedIn' => [],
        ];
    }

    public function getLabels(string $status, string $file, string $type, string $namespace): ?string
    {
        if ($type == EndpointProcessorHelper::REQUEST) {
            $currentRules = $this->requestService->getLocalRules($namespace);
            $mainRules = GitHelper::getRulesFromDocker($namespace);

            return $this->requestService->compareRules($currentRules, $mainRules);
        } elseif ($status == EndpointProcessorHelper::GIT_CHANGE_STATUS) {
            return $this->diffFields($file, $type == EndpointProcessorHelper::RESOURCE);
        }

        return __(key: 'analyze-endpoints::field_new', locale: $this->localization);
    }

    public function diffFields(string $file, bool $resourceMode = false): string
    {
        $process = new Process(['git', 'diff', "$this->from...$this->to", '--', $file]);
        $process->run();

        $pattern = $resourceMode ? self::PATTERN_RESOURCE_FIELD : self::PATTERN_REQUEST_FIELD;

        [
            'added' => $added,
            'removed' => $removed
        ] = $this->extractKeysFromDiff($pattern, $process->getOutput());

        $format = fn (string $action, array $fields) => $fields ?
            __m(key: "analyze-endpoints::$action", replace: ['fields' => implode(',', $fields)], locale: $this->localization) : null;

        $messages = array_filter([
            $format('field_added', $added),
            $format('field_removed', $removed),
        ]);

        return $messages ? implode('; ', $messages) : __(key: 'analyze-endpoints::field_changed', locale: $this->localization);
    }

    protected function extractKeysFromDiff(string $pattern, string $output): array
    {
        $addedKeys = [];
        $removedKeys = [];

        foreach (preg_split('/\R/', $output) as $line) {
            $lineType = $line[0] ?? null;
            if (! in_array($lineType, ['+', '-'], true) || str_starts_with($line, $lineType.$lineType.$lineType)) {
                continue;
            }

            $isAdded = str_starts_with($line, '+');

            if (preg_match($pattern, $line, $m)) {
                if ($isAdded) {
                    $addedKeys[] = __f(markdown: MarkdownSupport::CODE, text: $m[1]);
                } else {
                    $removedKeys[] = __f(markdown: MarkdownSupport::CODE, text: $m[1]);
                }
            }
        }

        return [
            'added' => array_unique($addedKeys),
            'removed' => array_unique($removedKeys),
        ];
    }
}
