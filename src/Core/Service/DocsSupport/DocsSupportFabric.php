<?php

namespace OM\MorphTrack\Core\Service\DocsSupport;

use OM\MorphTrack\Core\Service\DocsSupport\Redoc\RedocHelper;
use OM\MorphTrack\Core\Service\DocsSupport\Swagger\SwaggerHelper;
use OM\MorphTrack\Core\Service\DocsSupport\Scramble\ScrambleHelper;
use OM\MorphTrack\DocsConfig;
use OM\MorphTrack\Endpoints\Dto\Configuration\EndpointsConfig;

class DocsSupportFabric
{
    public function __construct(protected EndpointsConfig $endpointsConfig) {}

    public function make(): ?DocsHelper
    {
        $class = $this->getUsedClass();

        if ($class === null) {
            return null;
        }

        /** @var DocsHelper $instance */
        $instance = new $class();
        $instance->config = $this->endpointsConfig;

        return $instance;
    }

    protected function getUsedClass(): ?string
    {
        return $this->getClasses($this->endpointsConfig->globalConfig->docsConfig);
    }

    protected function getClasses(DocsConfig $docsConfig): ?string
    {
        return match (true) {
            $docsConfig->useScramble() => ScrambleHelper::class,
            $docsConfig->useSwagger()  => SwaggerHelper::class,
            $docsConfig->useRedoc() => RedocHelper::class,
            default => NullDocsHelper::class,
        };
    }
}