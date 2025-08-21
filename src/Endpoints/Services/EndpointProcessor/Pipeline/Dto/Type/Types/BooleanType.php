<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types;

class BooleanType extends Type
{
    protected function getType(): string
    {
        return 'boolean';
    }
}
