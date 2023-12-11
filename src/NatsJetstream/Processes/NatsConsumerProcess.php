<?php

namespace FrockDev\ToolsForHyperf\NatsJetstream\Processes;

use FrockDev\ToolsForHyperf\NatsJetstream\Events\AfterConsume;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\AfterSubscribe;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\BeforeConsume;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\BeforeSubscribe;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\FailToConsume;
use FrockDev\ToolsForHyperf\NatsJetstream\NatsDriverFactory;
use FrockDev\ToolsForHyperf\NatsJetstream\NatsJetstreamGrpcDriver;
use Google\Protobuf\Internal\Message;
use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class NatsConsumerProcess extends AbstractProcess
{
    private string $inputType;
    private string $subject;

    private object $endpoint;
    private ?string $queue;

    private NatsJetstreamGrpcDriver $driver;
    private string $poolName;

    private EventDispatcherInterface $dispatcher;
    private string $streamName;
    private int $processLag;

    public function __construct(
        ContainerInterface $container,
        object $endpoint,
        string $subject,
        string $poolName,
        ?string $queue = '',
        string $streamName = '',
        int $processLag = 1,
    )
    {
        parent::__construct($container);
        $this->endpoint = $endpoint;
        $this->inputType = ($endpoint)::GRPC_INPUT_TYPE;
        $this->subject = $subject;
        $this->queue = $queue;
        $this->poolName = $poolName;
        $this->streamName = $streamName;
        $this->processLag = $processLag;

        $this->driver = $this->container->get(NatsDriverFactory::class)
            ->get($this->poolName);

        if ($container->has(EventDispatcherInterface::class)) {
            $this->dispatcher = $container->get(EventDispatcherInterface::class);
        }

        $this->poolName = $poolName;
        $this->container = $container;
    }

    public function handle(): void
    {
        $this->dispatcher?->dispatch(new BeforeSubscribe([$this->subject]));
            if ($this->streamName!='') {
                $this->driver->subscribeToStream(
                    $this->subject,
                    $this->streamName,
                    function (Message $data) {
                        try {
                            $this->dispatcher?->dispatch(new BeforeConsume([$this->subject]));
                            // todo need context
                            // i think context we should take upper from driver
                            /** @var Message $result */
                            $result = $this->endpoint->__invoke($data);
                            $this->dispatcher?->dispatch(new AfterConsume([$this->subject]));
                            return $result->serializeToJsonString();
                        } catch (\Throwable $throwable) {
                            $this->dispatcher?->dispatch(new FailToConsume($throwable, json_decode($data->serializeToJsonString(), true)));
                            return null;
                        }
                    },
                    $this->inputType
                );
            } else {
                $this->driver->subscribe(
                    $this->subject,
                    $this->queue,
                    function (Message $data) {
                        try {
                            $this->dispatcher?->dispatch(new BeforeConsume([$this->subject]));
                            // todo need context
                            // i think context we should take upper from driver
                            /** @var Message $result */
                            $result = $this->endpoint->__invoke($data);
                            $this->dispatcher?->dispatch(new AfterConsume([$this->subject]));
                            return $result->serializeToJsonString();
                        } catch (\Throwable $throwable) {
                            $this->dispatcher?->dispatch(new FailToConsume($throwable, json_decode($data->serializeToJsonString(), true)));
                            return null;
                        }
                    },
                    $this->inputType
                );
            }

            $this->dispatcher?->dispatch(new AfterSubscribe([$this->subject]));
        while (true) {
            $this->driver->process();
            usleep($this->processLag);
        }
    }
}