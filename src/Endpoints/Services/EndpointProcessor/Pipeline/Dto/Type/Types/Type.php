<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types;

abstract class Type
{
    public function __construct(
        protected mixed $value = null,
    ) {}

    public function toString(): string
    {
        return (string) $this->value;
    }

    abstract protected function getType(): string;
}
