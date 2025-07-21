<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline;

use Illuminate\Validation\Rules\In;

class RequestService
{
    public function __construct(protected string $localization) {}

    public function getLocalRules(string $namespace): array
    {
        return (new $namespace())->rules();
    }

    public function compareRules(array $current, array $main): ?string
    {
        $added = array_diff_key($current, $main);
        $removed = array_diff_key($main, $current);

        if (!$added && !$removed) {
            $rules = $this->compareFields($current, $main);

            return $rules ?
                __m('analyze-endpoints::rule_added', ['fields' => $rules], $this->localization) :
                null;
        }

        $format = fn(string $key, array $fields) => $fields
            ? __m("analyze-endpoints::$key", ['fields' => implode(', ', array_keys($fields))], $this->localization)
            : null;

        $messages = array_filter([
            $format('field_added', $added),
            $format('field_removed', $removed),
        ]);

        return $messages ? implode('; ', $messages) : __('analyze-endpoints::field_changed', [], $this->localization);
    }

    protected function compareFields(array $current, array $main): string
    {
        $diffs = [];

        foreach ($current as $field => $rulesCurrent) {
            $rulesMain = $main[$field] ?? [];

            if($rulesMain == $rulesCurrent) {
                continue;
            }

            foreach ($rulesCurrent as $index => $ruleCurrent) {
                $ruleMain = $rulesMain[$index] ?? null;

                $value = $this->normalize($ruleCurrent, $ruleMain, $field);

                if(!$value) {
                    continue;
                }

                if(!isset($diffs[$field])) {
                    $diffs[$field] = $value;
                } else {
                    $diffs[$field] =  rtrim($diffs[$field], ', ')  . ', ' . $value;
                }
            }
        }

        return implode('; ', $diffs);
    }

    protected function normalize($ruleCurrent, $ruleMain, $field): ?string
    {
        if ($ruleCurrent instanceof In) {
            $ruleStr = (string) $ruleCurrent;
            $mainStr = $ruleMain instanceof In ? (string) $ruleMain : '';

            if ($ruleStr !== $mainStr) {
               return "$field: $ruleStr";
            }
        } elseif (is_string($ruleCurrent)) {
            if ($ruleCurrent !== $ruleMain) {
                return "$field: $ruleCurrent";
            }
        }

        return null;
    }
}