<?php

return [
    'route_pipelines' => [
        'post_filtered' => [],
    ],
    'field_change_locale' => 'uk',
    'pretty_print' => [
        'used' => 'flat_verbose',
        'types' => [
            'group_by_prefix' => [
                'default_route_prefix' => '[APP]',
                'route_prefix_groups' => [
                    '[APP]' => 'api/app',
                    '[WEB]' => 'api/web',
                    '[ADM]' => 'api/admin',
                ],
                'resolver' => \OM\MorphTrack\Endpoints\Services\Formatters\GroupByPrefixFormatter::class,
            ],
            'flat_verbose' => [
                'resolver' => \OM\MorphTrack\Endpoints\Services\Formatters\FlatVerboseFormatter::class,
            ],
            'table_markdown' => [
                'resolver' => \OM\MorphTrack\Endpoints\Services\Formatters\TableMarkdownFormatter::class,
            ],
        ],
    ],
    'scramble' => [
        'use' => true,
        'server' => 'Dev',
    ],
    'markdown_formatted' => true,
];
