<?php

namespace OM\MorphTrack\Instructions\Services;

use OM\MorphTrack\GlobalConfig;
use Symfony\Component\Process\Process;

class GenerateInstructionService
{
    public function __construct(protected GlobalConfig $config) {}

    public function getGitChangedFiles(): array
    {
        $process = new Process(['git', 'diff', '--name-status', 'origin/main...HEAD']);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        return collect(explode("\n", trim($process->getOutput())))
            ->filter(fn ($line) => preg_match('/^[AM]\t(.+)/', $line))
            ->map(fn ($line) => preg_replace('/^[AM]\t/', '', $line))
            ->all();
    }

    public function getInstructions(array $files)
    {
        $groups = InstructionGroupFactory::makeAll();

        foreach ($files as $file) {
            foreach ($groups as $group) {
                if (! $group->match($file)) {
                    continue;
                }

                $group->addFile($file);
            }
        }

        $instructions = [__m(key: 'generate-instruction::instructions_header', locale: $this->config->localization, prefix: '##')];

        foreach ($groups as $group) {
            $instructions = array_merge($instructions, $group->toLines($this->config));
        }

        if (count($instructions) === 1) {
            $instructions[] = __(key: 'generate-instruction::no_instruction', locale: $this->config->localization);
        }

        return $instructions;
    }
}
