<?php

namespace FrockDev\ToolsForHyperf\Annotations;

use Hyperf\Di\Annotation\AbstractAnnotation;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Nats extends AbstractAnnotation
{
    public string $subject;

    public ?string $queue = null;

    public ?string $name = 'unnamed';
    public int $nums = 1;

    public ?string $pool = null;

    public int $processLag = 1;

    public function __construct(
        string $subject,
        ?string $queue = null,
        ?string $name = 'unnamed',
        int $nums = 1,
        ?string $pool = null,
        int $processLag = 1
    )
    {
        $this->subject = $subject;
        $this->queue = $queue;
        $this->name = $name;
        $this->nums = $nums;
        $this->pool = $pool;
        $this->processLag = $processLag;
    }
}