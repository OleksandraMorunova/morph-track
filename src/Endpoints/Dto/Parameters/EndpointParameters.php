<?php

namespace OM\MorphTrack\Endpoints\Dto\Parameters;

class EndpointParameters
{
    public function __construct(
        public string $from,
        public string $to
    ) {}

    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}
