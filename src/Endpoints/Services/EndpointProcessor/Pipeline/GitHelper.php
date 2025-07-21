<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline;

use RuntimeException;
use Symfony\Component\Process\Process;

class GitHelper
{
    protected const CACHE_PROJECT_PATH = '/tmp/morph-track_';

    public static function getRulesFromDocker(string $namespace, string $branch = 'main'): array
    {
        $repoPath = '/var/www/html';
        $tmpDir = self::CACHE_PROJECT_PATH . $branch;

        if (! is_dir($tmpDir)) {
            self::createProject($branch, $repoPath, $tmpDir);
        }

        try {
            $isInsideDocker = file_exists('/.dockerenv') || getenv('SAIL');
            $artisanPath    = $tmpDir . '/artisan';

            if ($isInsideDocker) {
                $processParams = [
                    'php', $artisanPath,
                    'rules-dump', $namespace,
                ];
            } else {
                $processParams = [
                    'docker', 'exec', self::detectLaravelContainerName(),
                    'php', $artisanPath,
                    'rules-dump', $namespace,
                ];
            }

            $process = new Process($processParams);
            $process->run();

            if ($error = trim($process->getErrorOutput())) {
                throw new RuntimeException($error);
            }

            return json_decode($process->getOutput(), true) ?: [];
        } catch (\Throwable $e) {
            self::dropProject($repoPath, $tmpDir, $branch);
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    public static function dropProject(string $repoPath, string $tmpDir, string $branch = 'main'): void
    {
        $rmWorktree = new Process(['git', 'worktree', 'remove', '--force', $tmpDir], $repoPath);
        $rmWorktree->run();
        if (! $rmWorktree->isSuccessful()) {
            throw new RuntimeException("Failed to drop worktree for '{$branch}': " . $rmWorktree->getErrorOutput());
        }
    }

    protected static function createProject(string $branch, string $repoPath, string $tmpDir): void
    {
        $commitHash = (new Process(['git', 'rev-parse', $branch], $repoPath))->mustRun()->getOutput();
        (new Process(['git', 'worktree', 'prune'], $repoPath))->run();
        (new Process(['git', 'worktree', 'add', $tmpDir, trim($commitHash)], $repoPath))
            ->mustRun();
        (new Process(['cp', '-r', "{$repoPath}/vendor", "{$tmpDir}/vendor"]))->mustRun();
    }

    protected static function detectLaravelContainerName(): string
    {
        $process = new Process(['docker', 'ps', '--format', '{{.Names}} {{.Image}}']);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Cannot list Docker containers: ' . $process->getErrorOutput());
        }
        foreach (explode("\n", trim($process->getOutput())) as $line) {
            [$name, $image] = array_pad(explode(' ', $line, 2), 2, null);
            if (
                str_contains($image, 'laravel') ||
                str_contains($image, 'sail')    ||
                str_contains($name, 'api')
            ) {
                return $name;
            }
        }
        throw new RuntimeException('Laravel container not detected.');
    }
}
