<?php

namespace OM\MorphTrack\Instructions\Core;

use OM\MorphTrack\Instructions\Contracts\InstructionGroup;

abstract class AbstractInstructionGroup implements InstructionGroup
{
    protected array $files = [];

    public function addFile(string $file): void
    {
        $this->files[] = $file;
    }
}
