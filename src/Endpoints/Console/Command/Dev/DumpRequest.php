<?php

namespace OM\MorphTrack\Endpoints\Console\Command\Dev;

use Illuminate\Foundation\Http\FormRequest;

class DumpRequest extends CoreDump
{
    protected $signature = 'request-dump {class}';

    protected $description = 'Dump rules of the given FormRequest class as JSON';

    protected function subclass(): string
    {
        return FormRequest::class;
    }

    protected function process(string $class): void
    {
        $rules = (new $class)->rules();
        $serialized = $this->normalizeRules($rules);
        $this->line(json_encode($serialized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function normalizeRules(array $rules): array
    {
        $result = [];

        foreach ($rules as $field => $ruleSet) {
            $result[$field] = array_map(
                fn ($rule) => $this->normalizeRule($rule),
                $this->items($ruleSet)
            );
        }

        return $result;
    }

    protected function items(mixed $ruleSet): array
    {
        if (is_string($ruleSet)) {
            return explode('|', $ruleSet);
        } elseif (! is_array($ruleSet)) {
            return [$ruleSet];
        }

        return $ruleSet;
    }

    protected function normalizeRule(mixed $rule): mixed
    {
        if (! is_object($rule)) {
            return $rule;
        }

        if (method_exists($rule, '__toString')) {
            return (string) $rule;
        }

        return [
            'rule' => get_class($rule),
            'details' => method_exists($rule, 'toArray')
                ? $rule->toArray()
                : (array) $rule,
        ];
    }
}
