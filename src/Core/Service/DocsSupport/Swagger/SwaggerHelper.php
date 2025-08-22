<?php

namespace OM\MorphTrack\Core\Service\DocsSupport\Swagger;

use Illuminate\Routing\Route;
use OM\MorphTrack\Core\Service\DocsSupport\DocsHelper;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function array_pop;
use function count;
use function dirname;
use function explode;
use function implode;
use function is_array;
use function is_file;
use function ltrim;
use function str_replace;
use function str_starts_with;
use function strtolower;

class SwaggerHelper extends DocsHelper
{
    protected string $apiFileUrl;

    protected string $baseDir;

    protected array $yaml;

    protected ?string $serverUri = null;

    public function prepare(): void
    {
        $this->apiFileUrl = config('morph_track_config.docs_support.swagger.api_url', 'resources/docs/api/api.yaml');
        $this->serverUri = config('morph_track_config.docs_support.swagger.server_url', 'http://localhost');

        $this->baseDir = dirname($this->apiFileUrl);
        $this->yaml = $this->loadYaml($this->apiFileUrl);
    }

    public function buildUri(Route $route, string $method): array
    {
        $uri = preg_replace('#^api#', '', $route->uri());
        [$tag, $summary] = $this->getTags($uri, $method);

        $lowerMethod = strtolower($method);

        $path = $this->pathToJsonPointer($uri);

        $uri = $this->serverUri."#$tag/$lowerMethod"."_$path";

        return [$uri, $summary];
    }

    protected function getTags(string $uri, string $method): array
    {
        $opNode = $this->yaml['paths'][$uri] ?? null;
        if ($opNode === null) {
            return [$uri, null];
        }

        $resolved = $this->resolveNode($opNode, $this->baseDir);

        if (isset($resolved['tags']) || isset($resolved['summary'])) {
            $operation = $resolved;
        } else {
            $operation = $resolved[strtolower($method)] ?? $resolved['get'] ?? $resolved['post'] ?? null;
        }

        return [
            $operation['tags'][0] ?? null,
            $operation['summary'] ?? null,
        ];
    }

    protected function resolveNode(mixed $node, string $baseDir): mixed
    {
        if (! is_array($node)) {
            return $node;
        }

        if (isset($node['$ref']) && count($node) === 1) {
            return $this->loadRef($node['$ref'], $baseDir);
        }

        foreach ($node as $k => $v) {
            if (! is_array($v)) {
                continue;
            }

            $node[$k] = $this->resolveNode($v, $baseDir);
        }

        if (isset($node['$ref'])) {
            $resolved = $this->loadRef($node['$ref'], $baseDir);
            $node = \array_replace_recursive($resolved, \array_diff_key($node, ['$ref' => true]));
        }

        return $node;
    }

    private function loadRef(string $ref, string $baseDir): mixed
    {
        $parts = explode('#', $ref, 2);
        $filePart = $parts[0] ?? '';
        $pointer = $parts[1] ?? '';

        if ($filePart === '' || $filePart === null) {
            $doc = $this->loadYaml($this->apiFileUrl);
            $currentBase = dirname($this->apiFileUrl);
        } else {
            $filePath = $this->normalizePath($baseDir.DIRECTORY_SEPARATOR.$filePart);
            $doc = $this->loadYaml($filePath);
            $currentBase = dirname($filePath);
        }

        $target = $pointer !== '' ? $this->jsonPointerGet($doc, $pointer) : $doc;

        return $this->resolveNode($target, $currentBase);
    }

    protected function jsonPointerGet(array $doc, string $pointer): mixed
    {
        $pointer = ltrim($pointer, '/');
        if ($pointer === '') {
            return $doc;
        }

        $segments = explode('/', $pointer);
        $cur = $doc;

        foreach ($segments as $seg) {
            $seg = str_replace(['~1', '~0'], ['/', '~'], $seg);
            if (! is_array($cur) || ! \array_key_exists($seg, $cur)) {
                throw new RuntimeException("The specified JSON Pointer was not found: /$pointer");
            }
            $cur = $cur[$seg];
        }

        return $cur;
    }

    protected function loadYaml(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("File not found: $path");
        }

        return Yaml::parseFile($path);
    }

    protected function normalizePath(string $path): string
    {
        $parts = [];
        foreach (explode(DIRECTORY_SEPARATOR, $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);

                continue;
            }
            $parts[] = $part;
        }
        $prefix = str_starts_with($path, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';

        return $prefix.implode(DIRECTORY_SEPARATOR, $parts);
    }

    protected function pathToJsonPointer(string $path): string
    {
        $path = trim($path, '/');

        return str_replace(
            ['-', '/', '{', '}'],
            ['_', '_', '__', '__'],
            $path
        );
    }
}
