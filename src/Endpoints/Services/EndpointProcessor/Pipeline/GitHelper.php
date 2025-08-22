<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline;

use OM\MorphTrack\Core\Facades\EndpointsConfigFacade;
use OM\MorphTrack\Core\Facades\GlobalConfigFacade;
use RuntimeException;
use Symfony\Component\Process\Process;

class GitHelper
{
    public const BASE_PROJECT_PATH = '/tmp/morph-track_';

    public static function getRulesFromDocker(string $namespace, string $branch = 'main'): array
    {
        $workDir = GlobalConfigFacade::instance()->workingDir;
        $tmpDir = self::BASE_PROJECT_PATH.$branch;

        if (! is_dir($tmpDir)) {
            self::createProject($workDir, $branch, $tmpDir);
        }

        try {
            $isInsideDocker = file_exists('/.dockerenv') || getenv('SAIL');
            $artisanPath = $tmpDir.'/artisan';

            if ($isInsideDocker) {
                $processParams = [
                    'php', $artisanPath,
                    'request-dump', $namespace,
                ];
            } else {
                $processParams = [
                    'docker', 'exec', self::detectLaravelContainerName(),
                    'php', $artisanPath,
                    'request-dump', $namespace,
                ];
            }

            $process = new Process($processParams);
            $process->run();

            if ($error = trim($process->getErrorOutput())) {
                throw new RuntimeException($error);
            }

            return json_decode($process->getOutput(), true) ?: [];
        } catch (\Throwable $e) {
            self::dropProject($workDir, $tmpDir, $branch);
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    public static function dropProject(string $workDir, string $tmpDir, string $branch = 'main'): void
    {
        $rmWorktree = new Process(['git', 'worktree', 'remove', '--force', $tmpDir], $workDir);
        $rmWorktree->run();
        if (! $rmWorktree->isSuccessful()) {
            throw new RuntimeException("Failed to drop worktree for '{$branch}': ".$rmWorktree->getErrorOutput());
        }
    }

    protected static function createProject(string $workDir, string $branch, string $tmpDir): void
    {
        $repoPath = $workDir;
        $commitHash = (new Process(['git', 'rev-parse', $branch], $repoPath))->mustRun()->getOutput();
        (new Process(['git', 'worktree', 'prune'], $repoPath))->run();
        (new Process(['git', 'worktree', 'add', $tmpDir, trim($commitHash)], $repoPath))
            ->mustRun();
        (new Process(['cp', '-r', "$repoPath/vendor", "$tmpDir/vendor"]))->mustRun();
    }

    protected static function detectLaravelContainerName(): string
    {
        $process = new Process(['docker', 'ps', '--format', '{{.Names}} {{.Image}}']);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Cannot list Docker containers: '.$process->getErrorOutput());
        }
        foreach (explode("\n", trim($process->getOutput())) as $line) {
            [$name, $image] = array_pad(explode(' ', $line, 2), 2, null);
            if (
                str_contains($image, 'laravel') ||
                str_contains($image, 'sail') ||
                str_contains($name, 'api')
            ) {
                return $name;
            }
        }
        throw new RuntimeException('Laravel container not detected.');
    }
}
