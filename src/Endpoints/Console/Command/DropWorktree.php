<?php

namespace OM\MorphTrack\Endpoints\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\GitHelper;
use Throwable;

class DropWorktree extends Command
{
    protected $signature = 'worktree:drop {branch=origin/main}';

    protected $description = 'Delete a worktree';

    public function handle(): int
    {
        $branch = $this->argument('branch');
        $path = GitHelper::BASE_PROJECT_PATH.$branch;

        if (! File::exists($path)) {
            $this->error("❌ Worktree not found: $path");

            return self::FAILURE;
        }

        $isGit = File::exists($path.'/.git') || File::isDirectory($path.'/.git');

        if (! File::exists($path.'/artisan') && ! $isGit) {
            $this->warn("⚠️ Directory exists, but doesn't look like a worktree: $path");
            if (! $this->confirm('Do you still want to delete it?')) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        try {
            File::deleteDirectory($path);
            $this->info("✅ Worktree deleted: $path");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Failed to delete worktree: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
