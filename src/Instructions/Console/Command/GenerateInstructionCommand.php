<?php

namespace OM\MorphTrack\Instructions\Console\Command;

use Illuminate\Console\Command;
use OM\MorphTrack\GlobalConfig;
use OM\MorphTrack\Instructions\Services\GenerateInstructionService;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GenerateInstructionCommand extends Command
{
    protected $signature = 'generate:instructions';

    protected $description = 'Generates instructions for running migrations, seeders, and commands';

    public function handle(GenerateInstructionService $service, GlobalConfig $config): int
    {
        $this->info(__(key: 'generate-instruction::start', locale : $config->localization));

        $files = $service->getGitChangedFiles();

        if (! $files) {
            $this->error(__(key: 'generate-instruction::git_failed', locale : $config->localization));

            return CommandAlias::FAILURE;
        }

        $instructions = $service->getInstructions($files);

        $this->line(implode("\n", $instructions));

        return CommandAlias::SUCCESS;
    }
}
