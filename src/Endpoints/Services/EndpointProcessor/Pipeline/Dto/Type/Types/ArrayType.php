<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types;

class ArrayType extends Type
{
    public function __construct(mixed $value = null)
    {
        parent::__construct($value);
    }

    public function toString(): string
    {
        dump($this->value);
        return implode(', ', $this->value);
    }

    protected function getType(): string
    {
        return 'array';
    }
}