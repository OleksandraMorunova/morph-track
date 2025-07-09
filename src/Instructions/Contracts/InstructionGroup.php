<?php

namespace OM\MorphTrack\Instructions\Contracts;

use OM\MorphTrack\GlobalConfig;

interface InstructionGroup
{
    public function match(string $file): bool;

    public function toLines(GlobalConfig $config): array;
}
