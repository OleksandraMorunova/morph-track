<?php

namespace OM\MorphTrack\Instructions\Services\Groups;

use OM\MorphTrack\GlobalConfig;
use OM\MorphTrack\Instructions\Core\AbstractInstructionGroup;
use OM\MorphTrack\MarkdownSupport;

class CommandGroup extends AbstractInstructionGroup
{
    public function match(string $file): bool
    {
        return str_starts_with($file, 'app/Console/Commands/');
    }

    public function toLines(GlobalConfig $config): array
    {
        if (empty($this->files)) {
            return [];
        }

        $lines = [__(key: 'generate-instruction::command', locale: $config->localization)];
        foreach ($this->files as $file) {
            $fullPath = base_path($file);
            if (! file_exists($fullPath)) {
                continue;
            }

            $contents = file_get_contents($fullPath);
            preg_match("/signature\s*=\s*[\"']([^\"']+)[\"']/", $contents, $matches);
            if (! empty($matches[1])) {
                $lines[] = '  - '.__f(markdown: MarkdownSupport::CODE, text: "php artisan $matches[1]");
            }
        }

        return $lines;
    }
}
