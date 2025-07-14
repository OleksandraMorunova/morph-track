<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointAnalyzer;

use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;
use OM\MorphTrack\MarkdownSupport;
use Symfony\Component\Console\Output\OutputInterface;

class EndpointAnalyzerService
{
    public function __construct(
        protected EndpointsConfig $config,
        protected OutputInterface $output
    ) {}

    public function displayChanges(?string $lines = null): void
    {
        if (! $lines) {
            $this->output->writeln(__(key: 'analyze-endpoints::no_changes_detected', locale: $this->config->globalConfig->localization));

            return;
        }

        $this->output->writeln(__m(key: 'analyze-endpoints::changes_detected', locale: $this->config->globalConfig->localization, markdown: MarkdownSupport::HEADING_H2));
        $this->output->writeln($lines);
    }

    public function prettyPrint(array $routes): string
    {
        return (new PrettyPrintFabric($this->config))->create($routes);
    }
}
