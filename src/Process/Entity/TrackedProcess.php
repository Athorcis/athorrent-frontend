<?php

namespace Athorrent\Process\Entity;

use Athorrent\Process\Process;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Symfony\Component\Clock\DatePoint;
use function exec;
use function gethostname;
use function getmypid;
use const DIRECTORY_SEPARATOR;

#[ORM\Entity]
class TrackedProcess
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected int $id;

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    protected int $pid;

    #[ORM\Column(type: 'string', length: 253, nullable: false, options: ['collation' => 'utf8mb4_bin'])]
    protected string $host;

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    protected int $tracker;

    #[ORM\Column(type: 'text', nullable: false, options: ['collation' => 'utf8mb4_bin'])]
    protected string $cmd;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    protected DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    protected DateTimeImmutable $lastHeartbeatAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $terminatedAt = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $interrupted = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $exitCode = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['collation' => 'utf8mb4_bin'])]
    protected ?string $errorOutput = null;

    public function __construct(int $pid, $cmd, DateTimeImmutable $startedAt)
    {
        $this->pid = $pid;
        $this->host = gethostname();
        $this->tracker = getmypid();
        $this->cmd = $cmd;
        $this->startedAt = $startedAt;
        $this->lastHeartbeatAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function updateLastHeartbeat(): void
    {
        $this->lastHeartbeatAt = new DateTimeImmutable();
    }

    public function isRunning(): bool
    {
        if ($this->terminatedAt instanceof DateTimeImmutable) {
            return false;
        }

        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->lastHeartbeatAt->getTimestamp();

        return $diff < 10;
    }

    public function isSuccessful(): bool
    {
        return $this->exitCode === 0 && !$this->interrupted;
    }

    public function getTerminatedAt(): ?DateTimeImmutable
    {
        return $this->terminatedAt;
    }

    public function setTerminatedAt(DateTimeImmutable $terminatedAt): void
    {
        $this->terminatedAt = $terminatedAt;
    }

    public function setInterrupted(bool $interrupted): void
    {
        $this->interrupted = $interrupted;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    public function getErrorOutput(): ?string
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(string $errorOutput): void
    {
        $this->errorOutput = $errorOutput;
    }

    public function getStatus(): string
    {
        if ($this->isRunning()) {
            return 'running';
        }

        return $this->isSuccessful() ? 'success' : 'error';
    }

    protected function getKillCommand(): string
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return "tasklist /PID $this->pid";
        }

        return "kill $this->pid";
    }

    public function stop(): void
    {
        if ($this->isRunning()) {
            if ($this->host !== gethostname()) {
                throw new RuntimeException('this process was started on another host');
            }

            // Impossible d'utiliser posix_kill car cette fonction fait
            // le kill sur le host et non pas dans le conteneur
            exec($this->getKillCommand(), $output, $exitCode);
        }
    }

    public static function create(Process $process): self
    {
        if (!$process->isRunning()) {
            throw new RuntimeException('only running processes can be converted to entities');
        }

        $startedAt = DatePoint::createFromFormat(
            format: 'U.u',
            datetime: number_format($process->getStartTime(), 6, '.', ''),
            timezone: new DateTimeZone('Europe/Paris'),
        );

        return new static($process->getPid(), $process->getCommandLine(), $startedAt);
    }
}
