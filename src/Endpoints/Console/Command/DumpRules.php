<?php

namespace OM\MorphTrack\Endpoints\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DumpRules extends Command
{
    protected $signature = 'rules-dump {class}';

    protected $description = 'Dump rules of the given FormRequest class as JSON';

    public function handle(): int
    {
        $class = $this->argument('class');

        if (! class_exists($class)) {
            $this->error("Class $class does not exist");
            $this->line(json_encode([]));

            return CommandAlias::FAILURE;
        }

        if (! is_subclass_of($class, FormRequest::class)) {
            $this->error("Class $class is not a FormRequest");

            return CommandAlias::FAILURE;
        }

        $rules = (new $class)->rules();

        $this->line(json_encode($rules));

        return CommandAlias::SUCCESS;
    }
}
