<?php

declare(strict_types=1);

namespace SymfonyClient\CLI;

use Shared\Domain\Event\DomainEventSubscriber;
use Shared\Infrastructure\Bus\Event\DomainEventSubscriberLocator;
use Shared\Infrastructure\Bus\Event\MessageQueueNameFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function file_put_contents;
use function str_replace;

final class GenerateSupervisorQueuesCLI extends Command
{
    protected static $defaultName = 'supervisor:queue:configure';
    
    private const COMMAND_PATH = 'command-path';
    private const NUMBER_PROCESSES_PER_QUEUE = 1;
    private const EVENTS_TO_PROCESS = 10;
    private const SUPERVISOR_PATH = __DIR__ . '/../../build/supervisor';

    public function __construct(
        private DomainEventSubscriberLocator $locator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate the supervisor configuration for every subscriber')
            ->addArgument(self::COMMAND_PATH, InputArgument::OPTIONAL, 'Path on this is gonna be deployed', '/app');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = (string) $input->getArgument(self::COMMAND_PATH);

        /** @var DomainEventSubscriber $subscriber */
        foreach ($this->locator->all() as $subscriber) {
            $this->configSupervisorQueue($path, $subscriber);
        }

        return self::SUCCESS;
    }
    
    private function configSupervisorQueue(string $path, DomainEventSubscriber $subscriber): void
    {
        $queue = MessageQueueNameFormatter::format($subscriber);
        $subscriberName = MessageQueueNameFormatter::shortFormat($subscriber);
        
        $fileContent = str_replace(
            [
                '{queue_name}',
                '{path}',
                '{processes}',
                '{events_to_process}'
            ],
            [
                $queue,
                $path,
                self::NUMBER_PROCESSES_PER_QUEUE,
                self::EVENTS_TO_PROCESS
            ],
            $this->template()
        );
        
        file_put_contents($this->fileName($subscriberName), $fileContent);
    }
    
    private function template(): string
    {
        return <<<EOF
[program:{queue_name}]
command      = {path}/apps/SymfonyClient/bin/console event:consume --env=prod {queue_name} {events_to_process}
process_name = %(program_name)s_%(process_num)02d
numprocs     = {processes}
startsecs    = 1
startretries = 10
exitcodes    = 2
stopwaitsecs = 300
autostart    = true
EOF;

    }

    private function fileName(string $queue): string
    {
        return sprintf('%s/%s.ini', self::SUPERVISOR_PATH, $queue);
    }
}
