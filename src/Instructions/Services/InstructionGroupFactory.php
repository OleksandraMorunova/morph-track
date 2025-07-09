<?php

namespace OM\MorphTrack\Instructions\Services;

use OM\MorphTrack\Instructions\Core\AbstractInstructionGroup;
use OM\MorphTrack\Instructions\Services\Groups\CommandGroup;
use OM\MorphTrack\Instructions\Services\Groups\MigrationGroup;
use OM\MorphTrack\Instructions\Services\Groups\SeederGroup;

class InstructionGroupFactory
{
    /**
     * @return AbstractInstructionGroup[]
     */
    public static function makeAll(): array
    {
        return [
            new MigrationGroup,
            new SeederGroup,
            new CommandGroup,
        ];
    }
}
