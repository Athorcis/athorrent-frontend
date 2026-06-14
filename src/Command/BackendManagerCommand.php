<?php

namespace Athorrent\Command;

use Athorrent\Backend\BackendManager;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use React\Socket\SocketServer;
use Seld\Signal\SignalHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use function React\Async\async;
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
            Loop::get()->futureTick(async(function () use($self) {
                $this->backendManager->update();
                $self->reset();
            }));
        });

        $this->backendManager->run();

        return Command::SUCCESS;
    }

    protected function handleRequest(ServerRequestInterface $request)
    {
        $path = $request->getUri()->getPath();

        try {
            if ($path === '/healthz' && $request->getMethod() === 'GET') {
                $backendCount = $this->backendManager->getBackendCount();
                $failedBackendsCount = $this->backendManager->getFailedBackendsCount();

                return Response::json([
                    'status' => $failedBackendsCount > 0 ? 'error' : 'ok',
                    'count' => $backendCount,
                    'fail_count' => $failedBackendsCount,
                ]);
            }
            elseif ($path === '/user/add' && $request->getMethod() === 'POST') {
                $this->backendManager->addUser((int)$request->getQueryParams()['id']);

                return Response::json([
                    'status' => 'ok',
                ]);
            }
            elseif ($path === '/user/remove' && $request->getMethod() === 'DELETE') {
                $this->backendManager->removeUser((int)$request->getQueryParams()['id']);

                return Response::json([
                    'status' => 'ok',
                ]);
            }
            elseif ($path === '/user/detach' && $request->getMethod() === 'DELETE') {
                $this->backendManager->detachUser((int)$request->getQueryParams()['id']);

                return Response::json([
                    'status' => 'ok',
                ]);
            }
            elseif ($path === '/clear' && $request->getMethod() === 'DELETE') {
                $this->backendManager->clear();

                return Response::json([
                    'status' => 'ok',
                ]);
            }
        } catch (HttpExceptionInterface $e) {
            return Response::json(['status' => 'error', 'message' => $e->getMessage()])->withStatus($e->getStatusCode());
        }
        catch (Throwable $e) {
            dump($e);
            return Response::json(['status' => 'error', 'message' => $e->getMessage()])->withStatus(500);
        }

        return Response::plaintext("Not Found\n")->withStatus(404);
    }

    protected function registerHealthEndpoint(OutputInterface $output): callable
    {
        $server = new HttpServer(async($this->handleRequest(...)));

        $socket = new SocketServer('0.0.0.0:8080');
        $server->listen($socket);
        $output->writeln('Health server listening on http://0.0.0.0:8080/healthz');

        return static function () use ($socket) {
            $socket->close();
        };
    }
}
