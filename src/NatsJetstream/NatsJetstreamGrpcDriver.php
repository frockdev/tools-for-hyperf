<?php
namespace FrockDev\ToolsForHyperf\NatsJetstream;
use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Message\Payload;
use Closure;
use Google\Protobuf\Internal\Message;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Engine\Channel;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Pool\SimplePool\Pool;
use Psr\Container\ContainerInterface;
use Hyperf\Pool\SimplePool\PoolFactory;
use Throwable;

class NatsJetstreamGrpcDriver
{

    protected Pool $pool;

    public function __construct(ContainerInterface $container, string $name)
    {
        $config = $container->get(ConfigInterface::class)->get('natsJetstream', [])[$name];
        $factory = $container->get(PoolFactory::class);
        $poolConfig = $config['pool'] ?? [];
        $poolConfig['max_idle_time'] = $this->getMaxIdleTime($config);

        $logger = $container->get(LoggerFactory::class)->get('default');

        $this->pool = $factory->get('natsJetstreamGrpc' . $name, function () use ($config, $logger) {
            $client = new Client(
                new Configuration($config['options']),
                $logger
            );
            if (!array_key_exists('autoconnect', $config) || $config['autoconnect']!==false) {
                $client->connect();
            }
            return $client;
        }, $poolConfig);
    }

    protected function getMaxIdleTime(array $config = []): int
    {
        $timeout = $config['timeout'] ?? intval(ini_get('default_socket_timeout'));

        $maxIdleTime = $config['pool']['max_idle_time'];

        if ($timeout < 0) {
            return $maxIdleTime;
        }

        return (int) min($timeout, $maxIdleTime);
    }

    public function publish(string $subject, Message $payload, $inbox = null): void
    {
        try {
            /** @var ConnectionInterface $connection */
            $connection = $this->pool->get();
            /** @var Client $client */
            $client = $connection->getConnection();
            $client->publish($subject, $payload->serializeToJsonString(), $inbox);
        } finally {
            isset($connection) && $connection->release();
        }

    }

    public function request(string $subject, Message $payload, Closure $callback, string $deserializeTo): void
    {
        $function = function (Payload $payload) use ($callback, $deserializeTo) {
            /** @var Message $result */
            $result = new $deserializeTo();
            $result->mergeFromJsonString($payload->body);
            return $callback($result);
        };

        try {
            /** @var ConnectionInterface $connection */
            $connection = $this->pool->get();
            /** @var Client $client */
            $client = $connection->getConnection();
            $client->request($subject, $payload->serializeToJsonString(), $function);
        } finally {
            isset($connection) && $connection->release();
        }
    }

    public function requestSync(string $subject, Message $payload, string $deserializeTo): Message
    {
        try {
            $channel = new Channel(1);
            $function = function (Payload $payload) use ($deserializeTo, $channel) {
                /** @var Message $deserializedMessage */
                $deserializedMessage = new $deserializeTo();
                $deserializedMessage->mergeFromJsonString($payload->body);
                $channel->push($deserializedMessage);
            };
        
            /** @var ConnectionInterface $connection */
            $connection = $this->pool->get();
            /** @var Client $client */
            $client = $connection->getConnection();
            $client->request($subject, $payload->serializeToJsonString(), $function);
            $client->process(10);
            $message = $channel->pop(0.001);
            isset($connection) && $connection->release();
            return $message;
        } catch (DriverException $exception) {
            isset($connection) && $connection->release();
            throw $exception;
        } catch (Throwable $exception) {
            isset($connection) && $connection->release();
            throw new DriverException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function subscribe(string $subject, string $queue, Closure $callback, string $deserializeTo): void
    {
        $function = function (Payload $payload) use ($callback, $deserializeTo) {
            /** @var Message $result */
            $result = new $deserializeTo();
            $result->mergeFromJsonString($payload->body);
            return $callback($result);
        };
        try {
            /** @var ConnectionInterface $connection */
            $connection = $this->pool->get();
            /** @var Client $client */
            $client = $connection->getConnection();
            if ($queue === '') {
                $client->subscribe($subject, $function);
            } else {
                $client->subscribeQueue($subject, $queue, $function);
            }
        } catch (Throwable $exception) {
            isset($connection) && $connection->release();
            throw new DriverException($exception->getMessage(), $exception->getCode(), $exception);
        } finally {
            isset($connection) && $connection->release();
        }
    }

    public function subscribeToStream(string $subject, string $streamName, Closure $callback, string $deserializeTo): void
    {
        $function = function (Payload $payload) use ($callback, $deserializeTo) {
            /** @var Message $result */
            $result = new $deserializeTo();
            $result->mergeFromJsonString($payload->body);
            return $callback($result);
        };
        try {
            /** @var ConnectionInterface $connection */
            $connection = $this->pool->get();
            /** @var Client $client */
            $client = $connection->getConnection();
            $jetStream = $client->getApi()->getStream($streamName);
            $consumer = $jetStream->getConsumer($streamName.'-'.uniqid('consumer-'));
            $consumer->getConfiguration()->setSubjectFilter($subject);
            $consumer->handle($function);
        } finally {
            isset($connection) && $connection->release();
        }
    }

    public function process(int $timeout = 10)
    {
        try {
            /** @var ConnectionInterface $connection */
            $connection = $this->pool->get();
            /** @var Client $client */
            $client = $connection->getConnection();
            $client->process($timeout);
        } catch (Throwable $exception) {
            isset($connection) && $connection->release();
            throw new DriverException($exception->getMessage(), $exception->getCode(), $exception);
        } finally {
            isset($connection) && $connection->release();
        }
    }
}