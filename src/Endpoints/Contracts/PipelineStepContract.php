<?php

namespace OM\MorphTrack\Endpoints\Contracts;

use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\EndpointPipelineContext;

interface PipelineStepContract
{
    public function handle(EndpointPipelineContext $context, \Closure $next): EndpointPipelineContext;
}
