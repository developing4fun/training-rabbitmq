<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event\RabbitMQ;

use AMQPEnvelope;
use AMQPQueue;
use AMQPQueueException;
use Shared\Domain\Event\DomainEventSubscriber;
use Shared\Infrastructure\Bus\Event\DomainEventJsonDeserializer;
use Throwable;
use const AMQP_NOPARAM;

final class RabbitMQDomainEventConsumer implements DomainEventConsumer
{
    private const REDELIVERY_COUNT_HEADER = 'redelivery_count';

    public function __construct(
        private RabbitMQConnection $connection,
        private DomainEventJsonDeserializer $deserializer,
        private string $exchangeName,
        private int $maxRetries
    ) {}

    public function consume(
        DomainEventSubscriber $subscriber,
        string $queueName
    ): void {
        try {
            $this->connection
                ->queue($queueName)
                ->consume(
                    $this->consumer($subscriber)
                );
        } catch (AMQPQueueException) {}
    }

    private function consumer(DomainEventSubscriber $subscriber): callable
    {
        return function(AMQPEnvelope $envelope, AMQPQueue $queue) use ($subscriber): bool {

            $domainEvent = $this->deserializer->deserialize($envelope->getBody());

            try {
                $subscriber->__invoke($domainEvent);
            } catch (Throwable $error) {
                $this->handleConsumeError($envelope, $queue);
                throw $error;
            }

            $queue->ack($envelope->getDeliveryTag());
            
            return false;
        };
    }

    private function handleConsumeError(AMQPEnvelope $envelope, AMQPQueue $queue): void
    {
        $this->shouldSendToDeadLetter($envelope)
            ? $this->sendToDeadLetter($envelope, $queue)
            : $this->sendToRetry($envelope, $queue);

        $queue->ack($envelope->getDeliveryTag());
    }

    private function getRedeliveryCount(AMQPEnvelope $envelope): int
    {
        return $envelope->getHeaders()[self::REDELIVERY_COUNT_HEADER] ?? 0;
    }

    private function shouldSendToDeadLetter(AMQPEnvelope $envelope): bool
    {
        return $this->getRedeliveryCount($envelope) >= $this->maxRetries;
    }

    private function sendToDeadLetter(AMQPEnvelope $envelope, AMQPQueue $queue):void
    {
        $this->sendMessageToQueue(RabbitMQExchangeNameFormatter::deadLetter($this->exchangeName), $envelope, $queue);
    }

    private function sendToRetry(AMQPEnvelope $envelope, AMQPQueue $queue): void
    {
        $this->sendMessageToQueue(RabbitMQExchangeNameFormatter::retry($this->exchangeName), $envelope, $queue);
    }

    private function sendMessageToQueue(string $exchangeName, AMQPEnvelope $envelope, AMQPQueue $queue): void
    {
        $this->connection
            ->exchange($exchangeName)
            ->publish(
                $envelope->getBody(),
                $queue->getName(),
                AMQP_NOPARAM,
                [
                    'message_id' => $envelope->getMessageId(),
                    'content_type' => $envelope->getContentType(),
                    'content_encoding' => $envelope->getContentEncoding(),
                    'priority' => $envelope->getPriority(),
                    'headers' => [
                        self::REDELIVERY_COUNT_HEADER => $this->getRedeliveryCount($envelope) + 1,
                    ],
                ]
            );
    }
}
