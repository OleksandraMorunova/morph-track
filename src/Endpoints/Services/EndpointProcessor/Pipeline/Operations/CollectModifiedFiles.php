<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations;

use Closure;
use OM\MorphTrack\Endpoints\Contracts\PipelineStepContract;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\EndpointPipelineContext;
use Symfony\Component\Process\Process;

class CollectModifiedFiles implements PipelineStepContract
{
    public function handle(EndpointPipelineContext $context, Closure $next): EndpointPipelineContext
    {
        $params = $context->getParams();
        $lines = $this->collectFiles($params);

        $modifiedFiles = $this->getModifiedRequestAndResourceFiles($lines);
        $context->setFiles($modifiedFiles);

        return $next($context);
    }

    protected function collectFiles($params): array|bool
    {
        $process = new Process([
            'git', 'diff', '--name-status', "{$params->from}...{$params->to}",
        ]);
        $process->run();

        return preg_split('/\R/', trim($process->getOutput()));
    }

    protected function getModifiedRequestAndResourceFiles(array $lines): array
    {
        $files = [];

        foreach ($lines as $line) {
            [$status, $file] = preg_split('/\s+/', $line, 2);
            if (in_array($status, ['A', 'M'], true) && preg_match('#app/Http/(Requests|Resources)/.*\.php$#', $file)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}
