<?php

namespace FrockDev\ToolsForHyperf\NatsJetstream\Events;

class BeforeConsume
{
    public array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}