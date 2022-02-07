<?php

namespace Athorrent\Process\Command;

use Athorrent\Process\Entity\TrackedProcess;
use Athorrent\Process\Process;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function json_decode;
use function register_shutdown_function;
use const STDIN;

class TrackProcessCommand extends Command
{
    public const NAME = 'process:track';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct(self::NAME);
        $this->em = $em;
    }

    public function configure(): void
    {
        $this->addArgument('cmd', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // On décode la ligne de commande
        $command = json_decode($input->getArgument('cmd'), true, 512, JSON_THROW_ON_ERROR);

        // On crée le processus et on le démarre
        $process = Process::create($command);
        $process->setInput(STDIN);
        $process->setTimeout(null);

        $process->start();

        // On persiste l'état du processus en base
        $processEntity = TrackedProcess::create($process);
        $this->em->persist($processEntity);
        $this->em->flush();

        // On affiche l'id du processus démarré
        $output->write(sprintf('id:%s', $processEntity->getId()));

        // À l'arrêt du processus PHP
        register_shutdown_function(
            function () use ($process, $processEntity) {

                // On stoppe le processus si il s'exécute toujours
                $this->stopProcess($process, $processEntity);
            }
        );

        try {

            while ($process->isRunning()) {
                $processEntity->updateLastHeartbeat();
                $this->em->flush();

                sleep(2);
            }

            // On attend que processus se termine
            $exitCode = $process->getExitCode();
            $errorOutput = $process->getErrorOutput();

        } catch (Throwable $exception) {
            $exitCode = -1;
            $errorOutput = $exception->getMessage();
        } finally {

            // On met à jour l'état du processus en base
            $this->updateTerminatedProcess($processEntity, $exitCode, $errorOutput);
        }

        return $exitCode;
    }

    protected function stopProcess(Process $process, TrackedProcess $processEntity): void
    {
        // Si le processus est toujours en cours d'exécution
        if ($process->isRunning()) {

            // On stoppe le processus
            $exitCode = $process->stop();

            // On met à jour l'état du processus en base
            $this->updateTerminatedProcess($processEntity, $exitCode, true);
        }
    }

    protected function updateTerminatedProcess(
        TrackedProcess $processEntity,
        int $exitCode,
        string $errorOutput,
        bool $interrupted = false
    ): void {
        $processEntity->setTerminatedAt(new DateTime());
        $processEntity->setInterrupted($interrupted || $exitCode > 128);
        $processEntity->setExitCode($exitCode);
        $processEntity->setErrorOutput($errorOutput);

        $this->em->flush();
    }
}
