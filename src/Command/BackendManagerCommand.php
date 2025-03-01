<?php

namespace Athorrent\Command;

use Athorrent\Backend\BackendManager;
use React\EventLoop\Loop;
use Seld\Signal\SignalHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Promise\resolve;

class BackendManagerCommand extends Command
{
    public function __construct(private readonly BackendManager $backendManager)
    {
        parent::__construct('backend-manager:run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $interruptionHandler = SignalHandler::create(null, function (string $signalName, SignalHandler $self) use ($output) {
            $output->writeln(sprintf("Received signal %s", $signalName));

            $promise = null;

            // We have to execute this asynchronously or it crashes: https://github.com/php/php-src/pull/9028
            if (function_exists('pcntl_signal')) {
                $promise = $this->backendManager->stopAsync();
            }
            else {
                $this->backendManager->stop();
            }

            resolve($promise)->then(function () use ($self) {
                $self->reset();
            });
        });

        $updateHandler = SignalHandler::create([SignalHandler::SIGUSR1], function (string $signalName, SignalHandler $self) use ($output) {
            $output->writeln(sprintf("Received signal %s", $signalName));

            // We have to execute this asynchronously or it crashes: https://github.com/php/php-src/pull/9028
            Loop::get()->futureTick(function () use($self) {
                $this->backendManager->update();
                $self->reset();
            });
        });

        $this->backendManager->run();

        return 0;
    }
}
