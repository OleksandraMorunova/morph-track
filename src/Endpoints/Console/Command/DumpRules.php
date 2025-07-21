<?php

namespace OM\MorphTrack\Endpoints\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DumpRules extends Command
{
    protected $signature = 'rules-dump {class}';

    protected $description = 'Dump rules of the given FormRequest class as JSON';

    public function handle(): int
    {
        $class = $this->argument('class');

        if (! class_exists($class)) {
            $this->error("Class $class does not exist");
            $this->line(json_encode([]));

            return CommandAlias::FAILURE;
        }

        if (! is_subclass_of($class, FormRequest::class)) {
            $this->error("Class $class is not a FormRequest");

            return CommandAlias::FAILURE;
        }

        $rules = (new $class)->rules();
        $serialized = $this->normalizeRules($rules);
        $this->line(json_encode($serialized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return CommandAlias::SUCCESS;
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
