<?php

namespace OM\MorphTrack\Endpoints\Console\Command\Dev;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

abstract class CoreDump extends Command
{
    public function handle(): int
    {
        $class = $this->argument('class');

        if (! class_exists($class)) {
            $this->error("Class $class does not exist");
            $this->line(json_encode([]));

            return CommandAlias::FAILURE;
        }

        if (! is_subclass_of($class, $this->subclass())) {
            $this->error("Class $class is not a ".$this->subclass());

            return CommandAlias::FAILURE;
        }

        $this->process($class);

        return CommandAlias::SUCCESS;
    }

    abstract protected function subclass(): string;
    abstract protected function process(string $class): void;
}