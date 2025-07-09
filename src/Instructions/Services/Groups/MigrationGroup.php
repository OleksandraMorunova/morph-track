<?php

namespace OM\MorphTrack\Instructions\Services\Groups;

use OM\MorphTrack\GlobalConfig;
use OM\MorphTrack\Instructions\Core\AbstractInstructionGroup;

class MigrationGroup extends AbstractInstructionGroup
{
    public function match(string $file): bool
    {
        return str_starts_with($file, 'database/migrations/');
    }

    public function toLines(GlobalConfig $config): array
    {
        if (empty($this->files)) {
            return [];
        }

        return [__(key: 'generate-instruction::migrate', locale: $config->localization)];
    }
}
