<?php

namespace OM\MorphTrack\Core\Service\DocsSupport\Redoc;

use Illuminate\Routing\Route;
use OM\MorphTrack\Core\Service\DocsSupport\Swagger\SwaggerHelper;

class RedocHelper extends SwaggerHelper
{
    public function prepare(): void
    {
        parent::prepare();

        $serverUrl = $this->config->globalConfig->docsConfig->swaggerApiFileUrl;
        $this->serverUri = "$serverUrl/#tag/";
    }

    public function buildUri(Route $route, string $method): array
    {
        $uri = preg_replace('#^api#', '', $route->uri());

        [$tag, $summary] = $this->getTags($uri, $method);

        if ($tag !== null) {
            $tag = str_replace(' - ', '-', $tag);
            $tag = str_replace(' ', '-', $tag);
        }

        $lowerMethod = strtolower($method);
        $path = $this->pathToJsonPointer($uri);
        $uri = $this->serverUri.$tag."/paths/$path/$lowerMethod";

        return [$uri, $summary];
    }


    protected function pathToJsonPointer(string $path): string
    {
        $path = str_replace('~', '~0', $path);
        return str_replace('/', '~1', $path);
    }
}