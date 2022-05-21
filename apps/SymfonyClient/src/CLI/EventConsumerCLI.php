<?php

declare(strict_types=1);

namespace SymfonyClient\CLI;

use InvalidArgumentException;
use Shared\Infrastructure\Bus\Event\DomainEventSubscriberLocator;
use Shared\Infrastructure\Bus\Event\RabbitMQ\DomainEventConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function intval;

final class EventConsumerCLI extends Command
{
    protected static $defaultName = 'event:consume';
    private const QUEUE = 'queue';
    private const CHUNK = 'chunk';
    private const DEFAULT_CHUNK = 1;

    public function __construct(
        private DomainEventSubscriberLocator $locator,
        private DomainEventConsumer $consumer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Consume domain events')
            ->addArgument(self::QUEUE, InputArgument::REQUIRED, 'Queue name')
            ->addArgument(self::CHUNK, InputArgument::OPTIONAL, 'Quantity of events to process');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $queueName = $input->getArgument(self::QUEUE);
        $chunk = $this->obtainChunk($input);
        $subscriber = $this->locator->withQueueName($queueName);
        
        for ($i = 0; $i < $chunk; $i++) {
            $this->consumer->consume($subscriber, $queueName);
        }

        return self::SUCCESS;
    }

    private function obtainChunk(InputInterface $input): int
    {
        $param = $input->getArgument(self::CHUNK);

        if (null === $param) {
            return self::DEFAULT_CHUNK;
        }

        $value = intval($param);
        $this->assertValidChunk($value);

        return $value;
    }

    private function assertValidChunk(int $chunk): void
    {
        if ($chunk < 1) {
            throw new InvalidArgumentException('Invalid chunk value, it should be greater than 0');
        }
    }
}
