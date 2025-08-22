<?php

namespace OM\MorphTrack;

class DocsConfig
{
    public bool|string $useDocs;
    public ?string $swaggerApiFileUrl;

    public function __construct(public bool $includeNs = false)
    {
        $this->useDocs = config('morph_track_config.docs_support.use', false);
        $this->swaggerApiFileUrl = config('morph_track_config.docs_support.swagger.api_url', 'resources/docs/api/api.yaml');
    }

    public function useScramble(): bool
    {
        return $this->useDocs == 'scramble' && class_exists(\Dedoc\Scramble\Scramble::class);
    }

    public function useSwagger(): bool
    {
        return $this->useDocs == 'swagger' && file_exists($this->swaggerApiFileUrl);
    }

    public function useRedoc(): bool
    {
        return $this->useDocs == 'redoc' && file_exists($this->swaggerApiFileUrl);
    }
}