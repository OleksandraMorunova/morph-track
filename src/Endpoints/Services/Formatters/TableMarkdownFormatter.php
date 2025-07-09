<?php

namespace OM\MorphTrack\Endpoints\Services\Formatters;

use OM\MorphTrack\Endpoints\Core\AbstractFormatter;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\EndpointProcessorHelper;

class TableMarkdownFormatter extends AbstractFormatter
{
    protected array $headers;

    protected string $localization;

    public function __construct(public EndpointsConfig $config)
    {
        parent::__construct($config);
        $this->localization = $this->config->globalConfig->localization;

        $this->headers = [
            __(key: 'analyze-endpoints::column_method', locale: $this->localization),
            __(key: 'analyze-endpoints::column_uri', locale: $this->localization),
            __(key: 'analyze-endpoints::column_type', locale: $this->localization),
            __(key: 'analyze-endpoints::column_status', locale: $this->localization),
        ];
    }

    public function handle(array $routes): string
    {
        $rows = [];

        if ($this->config->includeNs) {
            array_splice($this->headers, 3, 0, [__(key: 'analyze-endpoints::column_method', locale: $this->localization)]);
        }

        $rows[] = $this->headers;
        $columnCount = count($this->headers);

        $this->processRows($rows, $routes, $this->config->includeNs, $columnCount);

        $columnWidth = [];
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $columnWidth[$i] = max($columnWidth[$i] ?? 0, mb_strlen($cell));
            }
        }

        $output = $this->buildTable($rows, $columnCount, $columnWidth);

        return implode(PHP_EOL, $output);
    }

    protected function processRows(array &$rows, array $routes, bool $includeNs, int $columnCount): void
    {
        foreach ($routes as $route => $items) {
            if (! preg_match('/^-  ([A-Z, ]+)\s+(.+)$/', $route, $routeMatch)) {
                continue;
            }

            $method = trim($routeMatch[1]);
            $uri = trim($routeMatch[2]);

            foreach ($items['body'] as $line) {
                $parsed = $this->parseLine($line, $includeNs);

                if ($parsed) {
                    [$type, $class, $status] = $parsed;

                    $row = [$method, $uri, $type];
                    if ($includeNs) {
                        $row[] = $class;
                    }
                    $row[] = $status;

                    $rows[] = array_slice($row, 0, $columnCount);
                }
            }
        }
    }

    protected function parseLine(string $line, bool $includeNs): ?array
    {
        if (! preg_match('/^\s*-\s+(request|resource)(?:\s+(.*?))?\s+–\s+(.*)$/u', $line, $matches)) {
            return null;
        }

        $type = $matches[1] === EndpointProcessorHelper::RESOURCE ? EndpointProcessorHelper::RESOURCE : $matches[1];
        $class = $includeNs ? ($matches[2] ?: '—') : '';
        $status = $matches[3];

        return [$type, $class, $status];
    }

    protected function buildTable(array $rows, int $columnCount, array $colWidths): array
    {
        $output = [];

        foreach ($rows as $i => $row) {
            $row = array_pad($row, $columnCount, '');

            $line = '|';
            foreach ($row as $j => $cell) {
                $line .= ' '.mb_str_pad($cell, $colWidths[$j]).' |';
            }
            $output[] = $line;

            if ($i === 0) {
                $divider = '|';
                foreach ($colWidths as $width) {
                    $divider .= str_repeat('-', $width + 2).'|';
                }
                $output[] = $divider;
            }
        }

        return $output;
    }
}
