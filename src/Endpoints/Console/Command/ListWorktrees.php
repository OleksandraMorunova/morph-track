<?php

namespace OM\MorphTrack\Endpoints\Console\Command;

use Illuminate\Console\Command;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\GitHelper;

class ListWorktrees extends Command
{
    protected $signature = 'worktree:list';
    protected $description = 'Display all local Git worktrees';

    public function handle(): int
    {
        $roots = glob(GitHelper::BASE_PROJECT_PATH.'*', GLOB_ONLYDIR);

        if (empty($roots)) {
            $this->warn('No worktree directories found');
            return self::SUCCESS;
        }

        $found = false;

        foreach ($roots as $root) {
            $rootName = basename($root);

            if (file_exists($root . '/.git') || file_exists($root . '/artisan')) {
                $this->line("- $root → <fg=cyan>$rootName</>");
                $found = true;
                continue;
            }

            $subdirs = glob("$root/*", GLOB_ONLYDIR);
            foreach ($subdirs as $branchPath) {
                if (!file_exists($branchPath . '/artisan') || !file_exists($branchPath . '/.git')) {
                    continue;
                }

                $branchName = basename($branchPath);
                $this->line("- $branchPath → <fg=cyan>$rootName/$branchName</>");
                $found = true;
            }
        }

        if (! $found) {
            $this->warn('No branch directories found');
        }

        return self::SUCCESS;
    }
}
