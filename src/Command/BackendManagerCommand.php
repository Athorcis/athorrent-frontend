<?php

namespace Athorrent\Command;

use Athorrent\Backend\BackendManager;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
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
        $destroyServer = $this->registerHealthEndpoint($output);

        $interruptionHandler = SignalHandler::create(null, function (string $signalName, SignalHandler $self) use ($output, $destroyServer) {
            $output->writeln(sprintf("Received signal %s", $signalName));

            $promise = null;

            // We have to execute this asynchronously or it crashes: https://github.com/php/php-src/pull/9028
            if (function_exists('pcntl_signal')) {
                $promise = $this->backendManager->stopAsync();
            }
            else {
                $this->backendManager->stop();
            }

            $destroyServer();

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

        return Command::SUCCESS;
    }

    protected function registerHealthEndpoint(OutputInterface $output): callable
    {
        $server = new HttpServer(function (ServerRequestInterface $request) {
            if ($request->getUri()->getPath() === '/healthz') {
                $backendCount = $this->backendManager->getBackendCount();
                $failedBackendsCount = $this->backendManager->getFailedBackendsCount();

                return Response::json([
                    'status' => $failedBackendsCount > 0 ? 'error' : 'ok',
                    'count' => $backendCount,
                    'fail_count' => $failedBackendsCount,
                ]);
            }

            return Response::plaintext("Not Found\n")->withStatus(404);
        });

        $socket = new SocketServer('0.0.0.0:8080');
        $server->listen($socket);
        $output->writeln('Health server listening on http://0.0.0.0:8080/healthz');

        return static function () use ($socket) {
            $socket->close();
        };
    }
}
