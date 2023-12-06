<?php

namespace FrockDev\ToolsForHyperf\Annotations;

#[\Attribute(\Attribute::TARGET_CLASS)]
class NatsJetstream extends Nats
{
    public string $streamName;

    public ?string $name = 'unnamedJetstream';

}