<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations;

use Closure;
use OM\MorphTrack\Endpoints\Contracts\PipelineStepContract;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\EndpointPipelineContext;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Process\Process;

class CollectModifiedFiles implements PipelineStepContract
{
    protected array $allowedRoots = [
        'app/Http/Requests',
        'app/Http/Resources',
    ];

    public function handle(EndpointPipelineContext $context, Closure $next): EndpointPipelineContext
    {
        $params = $context->getParams();
        $allChangedFiles = $this->collectChangedFiles($params);

        $files = $this->resolve($allChangedFiles);
        $context->setFiles($files);

        return $next($context);
    }

    protected function collectChangedFiles($params): array
    {
        $process = new Process(['git', 'diff', '--name-only', "$params->from...$params->to"]);
        $process->run();

        return preg_split('/\R/', trim($process->getOutput()));
    }

    protected function resolve(array $allRequestFiles): array
    {
        $changeFiles = [];
        foreach ($allRequestFiles as $file) {
            if (! $this->isAllowed($file)) {
                continue;
            }

            $name = basename($file);
            if (! str_ends_with($name, 'Request.php') && ! str_ends_with($name, 'Resource.php')) {
                continue;
            }

            $info = $this->classInfoResolver($file);
            if (! $info) {
                continue;
            }

            $this->find($file, $info, $changeFiles);
        }

        return $changeFiles;
    }

    protected function classInfoResolver(string $path): ?array
    {
        $code = file_get_contents($path);

        preg_match('/namespace\s+(.+);/', $code, $namespaceMatch);
        preg_match('/(?:class|enum|interface|trait)\s+(\w+)/', $code, $nameMatch);

        if (! isset($nameMatch[1])) {
            return null;
        }

        return [
            'fqcn' => ($namespaceMatch[1] ?? '').'\\'.$nameMatch[1],
            'className' => $nameMatch[1],
        ];
    }

    protected function isClassUsed(string $fileContent, string $fqcn, string $className): bool
    {
        $patterns = [
            '/new\s+'.preg_quote($className).'\b/',
            '/instanceof\s+'.preg_quote($className).'\b/',
            '/\b'.preg_quote($className).'::/',
            '/extends\s+'.preg_quote($className).'\b/',
            '/use\s+'.preg_quote($fqcn).'\s*;/',
            '/[^a-zA-Z0-9_]'.preg_quote($className).'[^a-zA-Z0-9_]/',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $fileContent)) {
                return false;
            }

            return true;
        }
    }

    public function isAllowed(string $path): bool
    {
        $realPath = realpath($path);

        return collect($this->allowedRoots)->some(fn ($root) => str_starts_with($realPath, realpath($root)));
    }

    public function find(string $pathToClassFile, array $info, array &$changeFiles): void
    {
        $usages = [];

        foreach ($this->allowedRoots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

            foreach ($files as $file) {
                $this->findClassUsages($file, $usages, $pathToClassFile, $info);
            }
        }

        $classes = empty($usages) ? [$pathToClassFile] : $usages;

        $changeFiles = array_values(array_filter(
            array_unique([...$changeFiles, ...$classes]),
            fn ($file) => $this->isAllowed($file)
        ));
    }

    protected function findClassUsages(SplFileInfo $file, array &$usages, string $pathToClassFile, array $info): void
    {
        if ($file->getExtension() !== 'php') {
            return;
        }

        $path = $file->getPathname();

        if (! $this->isAllowed($path) || realpath($path) === realpath($pathToClassFile)) {
            return;
        }

        if (! $this->isClassUsed(file_get_contents($path), $info['fqcn'], $info['className'])) {
            return;
        }

        $usages[] = $path;
    }
}
