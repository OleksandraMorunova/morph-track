<?php

namespace OM\MorphTrack\Endpoints\Console\Command;

use Illuminate\Console\Command;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;
use OM\MorphTrack\Endpoints\Dto\Parameters\EndpointParameters;
use OM\MorphTrack\Endpoints\Services\EndpointAnalyzer\EndpointAnalyzerService;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\EndpointProcessorService;
use Symfony\Component\Console\Command\Command as CommandAlias;

class AnalyzeEndpointCommand extends Command
{
    protected $signature = 'analyze:endpoints
                            {--from=origin/main : Base git ref}
                            {--to=HEAD : Target git ref}
                            {--debug : Include full class names}';

    protected $description = 'Show changed API endpoints by diffing Request and Resource classes';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');
        $includeNs = (bool) $this->option('debug');

        return $this->process($includeNs, $from, $to);
    }

    protected function process(bool $includeNs, string $from, string $to): int
    {
        $config = new EndpointsConfig($includeNs);

        $resolver = (new EndpointAnalyzerService($config, $this->output));

        $parameters = new EndpointParameters($from, $to);
        $routes = (new EndpointProcessorService($parameters, $config))->handle();

        $lines = $resolver->prettyPrint($routes);
        $resolver->displayChanges($lines);

        return CommandAlias::SUCCESS;
    }
}
