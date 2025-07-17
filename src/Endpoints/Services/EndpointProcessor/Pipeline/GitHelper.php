<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline;

use Symfony\Component\Process\Process;

class GitHelper
{
    protected const CACHE_PROJECT_PATH = 'bootstrap/cache/main_branch_tmp_morph-track_';
    public static function getRulesFromDocker(string $namespace, string $branch = 'main'): array
    {
        $repoPath = base_path();
        $tmpDir = base_path(self::CACHE_PROJECT_PATH . $branch);

        if (!is_dir($tmpDir)) {
            self::createProject($branch, $repoPath, $tmpDir);
        }

        try {
            $isInsideDocker = file_exists('/.dockerenv') || getenv('SAIL');
            $artisanPath = $tmpDir . '/artisan';

            $processParams = $isInsideDocker ? [
                'php',
                $tmpDir . '/artisan',
                'rules-dump',
                $namespace,
            ] : [
                'docker', 'exec', self::detectLaravelContainerName(),
                'php', $artisanPath, 'rules-dump', $namespace
            ];

            $process = new Process($processParams);
            $process->run();

            $error = trim($process->getErrorOutput());

            if ($error) {
                throw new \RuntimeException($error);
            }

            return json_decode($process->getOutput(), true) ?? [];
        } catch (\Throwable $e) {
            self::dropProject($repoPath, $tmpDir);

            throw new \RuntimeException($e->getMessage());
        }
    }

    public static function dropProject(?string $repoPath = null, ?string $tmpDir = null, string $branch = 'main'): void
    {
        $repoPath = $repoPath?? base_path();
        $tmpDir = $tmpDir ?? base_path(self::CACHE_PROJECT_PATH . $branch);

        $rmWorktree = new Process(['git', 'worktree', 'remove', '--force', $tmpDir], $repoPath);
        $rmWorktree->run();

        if (!$rmWorktree->isSuccessful()) {
            throw new \RuntimeException("Failed to drop project hash of '$branch': " . $rmWorktree->getErrorOutput());
        }
    }

    protected static function createProject(string $branch, string $repoPath, string $tmpDir): void
    {
        $commitHashProcess = new Process(['git', 'rev-parse', $branch], $repoPath);
        $commitHashProcess->run();

        if (!$commitHashProcess->isSuccessful()) {
            throw new \RuntimeException("Failed to get commit hash of '$branch': " . $commitHashProcess->getErrorOutput());
        }

        $commit = trim($commitHashProcess->getOutput());

        $pruneWorktrees = new Process(['git', 'worktree', 'prune'], $repoPath);
        $pruneWorktrees->run();

        $addWorktree = new Process(['git', 'worktree', 'add', $tmpDir, $commit], $repoPath);
        $addWorktree->run();

        if (!$addWorktree->isSuccessful()) {
            throw new \RuntimeException("Failed to create git worktree: " . $addWorktree->getErrorOutput());
        }

        $copyVendor = new Process(['cp', '-r', $repoPath . '/vendor', $tmpDir . '/vendor']);
        $copyVendor->run();

        if (!$copyVendor->isSuccessful()) {
            throw new \RuntimeException("Failed to copy vendor: " . $copyVendor->getErrorOutput());
        }
    }

    protected static function detectLaravelContainerName(): string
    {
        $process = new Process(['docker', 'ps', '--format', '{{.Names}} {{.Image}}']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Failed to list docker containers: " . $process->getErrorOutput());
        }

        $output = explode("\n", trim($process->getOutput()));

        foreach ($output as $line) {
            [$name, $image] = explode(' ', $line) + [null, null];

            if (
                str_contains($image, 'laravel') ||
                str_contains($image, 'sail') ||
                str_contains($name, 'api')
            ) {
                return $name;
            }
        }

        throw new \RuntimeException("Could not detect Laravel container name.");
    }

}
