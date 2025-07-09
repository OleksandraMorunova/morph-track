<?php

namespace OM\MorphTrack\Endpoints\Core;

use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;

abstract class AbstractFormatter
{
    public function __construct(public EndpointsConfig $config) {}

    abstract public function handle(array $routes): string;
}
