<?php

namespace OM\MorphTrack\Endpoints\Services\Formatters;

use OM\MorphTrack\Endpoints\Core\AbstractFormatter;

class FlatVerboseFormatter extends AbstractFormatter
{
    public function handle(array $routes): string
    {
        $output = [];

        foreach ($routes as $route => $items) {
            $output[] = $route;

            foreach ($items['body'] as $line) {
                $output[] = "$line";
            }

            $output[] = '';
        }

        return implode(PHP_EOL, $output);
    }
}
