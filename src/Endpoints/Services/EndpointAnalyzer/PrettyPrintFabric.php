<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointAnalyzer;

use OM\MorphTrack\Endpoints\Core\AbstractFormatter;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;

class PrettyPrintFabric
{
    protected EndpointsConfig $config;

    public function __construct(EndpointsConfig $config)
    {
        $this->config = $config;
    }

    public function create(array $routes): string
    {
        $prettyPrintClass = $this->config->getPrettyPrintClass();

        if (! class_exists($prettyPrintClass)) {
            throw new \RuntimeException("Class $prettyPrintClass does not exist.");
        }

        if (! in_array(AbstractFormatter::class, class_parents($prettyPrintClass))) {
            throw new \RuntimeException("Class $prettyPrintClass must implement the ".AbstractFormatter::class.' interface.');
        }

        /** @var AbstractFormatter $prettyPrintClass */
        return (new $prettyPrintClass($this->config))->handle($routes);
    }
}
