<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor;

use Illuminate\Pipeline\Pipeline;
use OM\MorphTrack\Endpoints\Contracts\PipelineStepContract;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;
use OM\MorphTrack\Endpoints\Dto\Parameters\EndpointParameters;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Dto\EndpointPipelineContext;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations\CollectModifiedFiles;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations\FilterUnchanged;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations\ProcessStatusFiles;
use OM\MorphTrack\Endpoints\Services\EndpointProcessor\Pipeline\Operations\ProcessUsagesRoutes;
use RuntimeException;

class EndpointProcessorService
{
    public function __construct(
        protected EndpointParameters $dto,
        protected EndpointsConfig $config
    ) {}

    public function handle(): array
    {
        $initialContext = new EndpointPipelineContext($this->dto, $this->config);

        $postFiltering = $this->config->postFiltering;

        if ($postFiltering) {
            foreach ($postFiltering as $class) {
                if (! is_subclass_of($class, PipelineStepContract::class)) {
                    throw new RuntimeException("Class $class must implement CheckEndpointContract");
                }
            }
        }

        /** @var EndpointPipelineContext $finalContext */
        $finalContext = app(Pipeline::class)
            ->send($initialContext)
            ->through([
                CollectModifiedFiles::class,
                ProcessStatusFiles::class,
                ProcessUsagesRoutes::class,
                FilterUnchanged::class,
                ...$postFiltering,
            ])
            ->thenReturn();

        return $finalContext->getFiltered();
    }
}
