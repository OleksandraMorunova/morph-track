<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto;

use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;
use OM\MorphTrack\Endpoints\Dto\Parameters\EndpointParameters;

class EndpointPipelineContext
{
    protected array $files = [];

    protected array $filtered = [];

    protected EndpointsConfig $config;

    public function __construct(protected EndpointParameters $params, ?EndpointsConfig $config = null)
    {
        $this->config = $config ?? new EndpointsConfig;
    }

    public function getParams(): EndpointParameters
    {
        return $this->params;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    public function setFiles(array $files = []): array
    {
        $this->files = $files;

        return $files;
    }

    public function getFiltered(): array
    {
        return $this->filtered;
    }

    public function setFiltered(array $filtered): array
    {
        $this->filtered = $filtered;

        return $filtered;
    }

    public function getConfig(): EndpointsConfig
    {
        return $this->config;
    }
}
