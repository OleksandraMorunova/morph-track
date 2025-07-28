<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\Type\Types;

use Illuminate\Validation\Rules\Enum;
use Throwable;

class ObjectType extends Type
{
    public function __construct(mixed $value = null)
    {
        parent::__construct($value);
        $this->normalize();
    }

    public function toString(): string
    {
        try {
            return (string) $this->value;
        } catch (Throwable $throwable) {
            if(is_array($this->value)) {
                return implode(',', $this->value);
            }
            return get_class($this->value);
        }
    }

    protected function getType(): string
    {
        return 'object';
    }

    protected function normalize(): void
    {
        if (is_array($this->value) && isset($this->value['rule'])) {
            $this->normalizeClasses($this->value['details']);
        }

        if ($this->value instanceof Enum) {
            $this->enum($this->value);
        }
    }

    protected function normalizeClasses(array|Enum $value): void
    {
        $class = $this->value['rule'];

        match ($class) {
            Enum::class => $this->enum($value),
            default => $this->custom($value),
        };
    }

    public function enum(array|Enum $rule): void
    {
        $getProtectedValue = function ($obj, $name) {
            $array = (array)$obj;
            $prefix = chr(0) . '*' . chr(0);
            dump($prefix);

            return $array[$prefix . $name];
        };

        $this->value = $getProtectedValue($rule, 'type');
    }

    public function custom(array $rule): void
    {
        $this->value = $rule[chr(0) . '*' . chr(0) . 'values'] ?? [];
    }
}
