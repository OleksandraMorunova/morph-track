<?php

namespace OM\MorphTrack;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use OM\MorphTrack\Endpoints\Console\Command\AnalyzeEndpointCommand;
use OM\MorphTrack\Instructions\Console\Command\GenerateInstructionCommand;
use OM\MorphTrack\MarkdownTranslator\MarkdownTranslator;

class AnalyzeEndpointServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            AnalyzeEndpointCommand::class,
            GenerateInstructionCommand::class,
        ]);

        $this->app->singleton('markdown-translator', function ($app) {
            $config = new GlobalConfig;

            return new MarkdownTranslator(
                translator: $app['translator'],
                locale: $config->localization,
                markdownFormatted: $config->markdownFormatted,
            );
        });

        require_once __DIR__.'/Helpers.php';

        $this->mergeConfigFrom(
            __DIR__.'/../config/morph_track_config.php',
            'morph_track_config'
        );
    }

    public function boot(): void
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__.'/../config/morph_track_config.php' => config_path('endpoints.php')],
                'morph_track_config'
            );

            $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');
        }
    }
}
