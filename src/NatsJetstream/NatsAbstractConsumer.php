<?php

namespace FrockDev\ToolsForHyperf\NatsJetstream;

use Hyperf\Nats\AbstractConsumer;
use Hyperf\Nats\Message;

/**
 * You need to extend this class to use NatsJetstream
 */
abstract class NatsAbstractConsumer extends AbstractConsumer
{
    protected string $streamName = '';

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    public function setStreamName(string $streamName): void
    {
        $this->streamName = $streamName;
    }


}