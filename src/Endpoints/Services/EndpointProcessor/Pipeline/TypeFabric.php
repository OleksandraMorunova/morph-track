<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline;

use InvalidArgumentException;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types\ArrayType;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types\NumberType;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types\ObjectType;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types\StringType;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types\Type;

class TypeFabric
{
    public function getTypeClass(mixed $rule): Type
    {
        if (is_string($rule)) {
            $class = StringType::class;
        } elseif (is_numeric($rule)) {
            $class = NumberType::class;
        } elseif (is_array($rule)) {
            $class = ArrayType::class;
        } elseif (is_object($rule)) {
            $class = ObjectType::class;
        } else {
            throw new InvalidArgumentException(sprintf('Unsupported rule type: %s', get_debug_type($rule)));
        }

        $this->specificTypeClass($rule, $class);

        return new $class($rule);
    }

    protected function specificTypeClass(mixed $rule, string &$class): void
    {
        if (is_array($rule) && isset($rule['rule'])) {
            $class = ObjectType::class;
        }
    }

    public function transform(mixed $rule): string
    {
        /** @var Type $class */
        $class = $this->getTypeClass($rule);

        return $class->toString();
    }
}
