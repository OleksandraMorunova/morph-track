<?php

namespace OM\MorphTrack\Instructions\Services\Groups;

use OM\MorphTrack\GlobalConfig;
use OM\MorphTrack\Instructions\Core\AbstractInstructionGroup;
use OM\MorphTrack\MarkdownSupport;

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

        $instruction = __(
            key: 'generate-instruction::migrate',
            locale: $config->localization
        );

        $command = __f(
            markdown: MarkdownSupport::CODE,
            text: 'php artisan migrate'
        );

        return ["$instruction $command"];
    }
}
