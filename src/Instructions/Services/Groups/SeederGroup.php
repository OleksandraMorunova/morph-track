<?php

namespace OM\MorphTrack\Instructions\Services\Groups;

use OM\MorphTrack\GlobalConfig;
use OM\MorphTrack\Instructions\Core\AbstractInstructionGroup;

class SeederGroup extends AbstractInstructionGroup
{
    public function match(string $file): bool
    {
        return str_starts_with($file, 'database/seeders/');
    }

    public function toLines(GlobalConfig $config): array
    {
        if (empty($this->files)) {
            return [];
        }

        $lines = [__(key: 'generate-instruction::seeder', locale: $config->localization)];
        foreach ($this->files as $file) {
            $class = pathinfo($file, PATHINFO_FILENAME);
            $markdown = $config->markdownFormatted ? '`' : '';
            $lines[] = "  - {$markdown}php artisan db:seed --class=$class$markdown";
        }

        return $lines;
    }
}
