<?php

namespace OM\MorphTrack\Endpoints\Services\EndpointProcessor;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class EndpointProcessorHelper
{
    public const GIT_CHANGE_STATUS = 'M';

    public const RESOURCES = 'Resources';

    public const RESOURCE = 'resource';

    public const REQUESTS = 'Requests';

    public const REQUEST = 'request';

    public static function pathToNamespace(string $file): string
    {
        $content = file_get_contents(base_path($file));
        preg_match('/^namespace\s+([^;]+);/m', $content, $m);
        $ns = $m[1] ?? 'App\\Http\\'.(Str::contains($file, self::RESOURCES) ? self::RESOURCES : self::REQUESTS);
        $class = pathinfo($file, PATHINFO_FILENAME);

        return "$ns\\$class";
    }

    public static function gitFileStatus($params, string $file): string
    {
        $process = new Process([
            'git', 'diff', '--name-status',
            "{$params->from}...{$params->to}", '--', $file,
        ]);
        $process->run();

        foreach (preg_split('/\R/', trim($process->getOutput())) as $line) {
            if (Str::contains($line, $file)) {
                return Str::before($line, "\t");
            }
        }

        return self::GIT_CHANGE_STATUS;
    }
}
