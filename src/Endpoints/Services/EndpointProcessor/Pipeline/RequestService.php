<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline;

use OM\MorphTrack\MarkdownSupport;

class RequestService
{
    protected string $namespace;

    protected string $localization;

    public function __construct(
        protected TypeFabric $typeFabric,
    ) {}

    public function getLocalRules(string $namespace): array
    {
        $this->namespace = $namespace;

        return (new $namespace)->rules();
    }

    public function compareRules(array $current, array $main, string $localization): ?string
    {
        $this->localization = $localization;

        [
            'keys' => $keysModifiedValue,
            'modified' => $modifiedValue
        ] = $this->compare($current, $main);

        if ($keysModifiedValue && $modifiedValue) {
            return $keysModifiedValue.', '.$modifiedValue;
        }

        if ($keysModifiedValue) {
            return $keysModifiedValue;
        }

        return $modifiedValue;
    }

    public function compareByKeys(array $modifiedCurrent, array $modifiedMain): ?string
    {
        $added = array_diff_key($modifiedCurrent, $modifiedMain);
        $removed = array_diff_key($modifiedMain, $modifiedCurrent);

        $format = function (string $key, array $fields) {
            $fields = $fields ? __f(markdown: MarkdownSupport::CODE, text: implode(', ', array_keys($fields))) : null;

            return $fields
                ? __m(key: "analyze-endpoints::$key",
                    replace: ['fields' => $fields],
                    locale: $this->localization
                ) : null;
        };

        $messages = array_filter([
            $format('field_added', $added),
            $format('field_removed', $removed),
        ]);

        return $messages ? implode('; ', $messages) : null;
    }

    protected function compare(array $current, array $main): array
    {
        $modifiedCurrent = $this->formatRules($current);
        $modifiedMain = $this->formatRules($main);

        $commonKeys = array_intersect_key($modifiedCurrent, $modifiedMain);

        $diffs = [];

        foreach ($commonKeys as $key => $currentValue) {
            $mainValue = $modifiedMain[$key];

            $currentParts = explode(':', $currentValue, 2);
            $mainParts = explode(':', $mainValue, 2);

            if (count($currentParts) < 2 || count($mainParts) < 2) {
                continue;
            }

            $currentRuleBody = explode(':', $currentValue, 2)[1] ?? '';
            $mainRuleBody = explode(':', $mainValue, 2)[1] ?? '';

            preg_match_all('/"([^"]+)"/', $currentRuleBody, $currentMatches);
            preg_match_all('/"([^"]+)"/', $mainRuleBody, $mainMatches);

            $currentValues = $currentMatches[1] ?? [];
            $mainValues = $mainMatches[1] ?? [];

            $unique = array_diff($currentValues, $mainValues);

            if (! empty($unique)) {
                $quoted = array_map(fn ($v) => "\"$v\"", $unique);
                $diffs[$key] = 'in:'.implode(',', $quoted);
            }
        }

        $diffs = $diffs ? __f(markdown: MarkdownSupport::CODE, text: implode('; ', $diffs)) : '';

        $rulesArray = $diffs ?
            __m(key: 'analyze-endpoints::rule_added',
                replace: ['fields' => $diffs],
                locale: $this->localization
            ) : null;

        return [
            'keys' => $this->compareByKeys($modifiedCurrent, $modifiedMain),
            'modified' => $rulesArray,
        ];
    }

    protected function formatRules(array $rules): array
    {
        $result = [];

        foreach ($rules as $key => $items) {
            $transformed = array_map(fn ($rule) => $this->typeFabric->transform($rule), $items);
            $result[$key] = $key.': '.implode('; ', $transformed);
        }

        return $result;
    }
}
