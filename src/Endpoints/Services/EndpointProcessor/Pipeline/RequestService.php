<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline;

class RequestService
{
    public function __construct(protected string $localization) {}

    public function getLocalRules(string $namespace): array
    {
        return (new $namespace())->rules();
    }

    public function compareRules(array $current, array $main): string
    {
        $added = array_diff_key($current, $main);
        $removed = array_diff_key($main, $current);

        $format = fn (string $key, array $fields) => $fields
            ? __m("analyze-endpoints::$key", ['fields' => implode(', ', array_keys($fields))], $this->localization)
            : null;

        $messages = array_filter([
            $format('field_added', $added),
            $format('field_removed', $removed),
        ]);

        return $messages ? implode('; ', $messages) : __('analyze-endpoints::field_changed', [], $this->localization);
    }
}
