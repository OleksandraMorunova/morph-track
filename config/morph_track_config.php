<?php

return [
    'working_dir' => '/var/www/html',
    'route_pipelines' => [
        'post_filtered' => [],
    ],
    'field_change_locale' => 'uk',
    'pretty_print' => [
        'used' => 'group_by_prefix',
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
    'docs_support' => [
        'use' => 'scramble',
        'scramble' => [
            'server' => 'dev',
        ],
        'swagger' => [
            'api_url' => 'resources/docs/api/api.yaml',
            'server_url' => 'http://localhost',
        ],
        'redoc' => [
            'server_url' => 'http://localhost',
        ],
    ],
    'markdown_formatted' => true,
];
