<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types;

class NumberType extends Type
{
    protected function getType(): string
    {
        return 'number';
    }
}
