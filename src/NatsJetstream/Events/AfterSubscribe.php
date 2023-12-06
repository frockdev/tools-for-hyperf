<?php

namespace FrockDev\ToolsForHyperf\NatsJetstream\Events;

class AfterSubscribe
{
    public array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}