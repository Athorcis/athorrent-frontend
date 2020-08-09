<?php

namespace Athorrent\Process\Entity;

use Athorrent\Process\Process;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use function exec;
use function gethostname;
use function getmypid;
use const DIRECTORY_SEPARATOR;

/**
 * @ORM\Entity
 */
class TrackedProcess
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false, options={"unsigned": true})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"unsigned": true})
     */
    protected $pid;

    /**
     * @var string
     * @ORM\Column(type="string", length=253, nullable=false, options={"collation": "utf8mb4_bin"})
     */
    protected $host;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"unsigned": true})
     */
    protected $tracker;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false, options={"collation": "utf8mb4_bin"})
     */
    protected $cmd;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $startedAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $lastHeartbeatAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $terminatedAt;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $interrupted;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $exitCode;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true, options={"collation": "utf8mb4_bin"})
     */
    protected $errorOutput;

    public function __construct(int $pid, $cmd, DateTime $startedAt)
    {
        $this->pid = $pid;
        $this->host = gethostname();
        $this->tracker = getmypid();
        $this->cmd = $cmd;
        $this->startedAt = $startedAt;
        $this->lastHeartbeatAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @return DateTime
     */
    public function getStartedAt(): DateTime
    {
        return $this->startedAt;
    }

    public function updateLastHearbeat(): void
    {
        $this->lastHeartbeatAt = new DateTime();
    }

    public function isRunning(): bool
    {
        if ($this->terminatedAt instanceof DateTime) {
            return false;
        }

        $now = new DateTime();
        $diff = $now->getTimestamp() - $this->lastHeartbeatAt->getTimestamp();

        return $diff < 10;
    }

    public function isSuccessful(): bool
    {
        return $this->exitCode === 0 && !$this->interrupted;
    }

    /**
     * @return DateTime
     */
    public function getTerminatedAt(): DateTime
    {
        return $this->terminatedAt;
    }

    /**
     * @param DateTime $terminatedAt
     */
    public function setTerminatedAt(DateTime $terminatedAt): void
    {
        $this->terminatedAt = $terminatedAt;
    }

    /**
     * @param bool $interrupted
     */
    public function setInterrupted(bool $interrupted): void
    {
        $this->interrupted = $interrupted;
    }

    /**
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @param int $exitCode
     */
    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    /**
     * @return string
     */
    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    /**
     * @param string $errorOutput
     */
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

    public function stop(): void
    {
        if ($this->isRunning()) {
            if ($this->host !== gethostname()) {
                throw new RuntimeException('this process was started on another host');
            }

            if ('\\' === DIRECTORY_SEPARATOR) {
                $killCommand = "tasklist /PID $this->pid";
            }
            else {
                $killCommand = "kill $this->pid";
            }

            // Impossible d'utiliser posix_kill car cette fonction fait
            // le kill sur le host et non pas dans le conteneur
            exec($killCommand, $output, $exitCode);
        }
    }

    public static function create(Process $process): self
    {
        if (!$process->isRunning()) {
            throw new RuntimeException('only running processes can be converted to entities');
        }

        $startedAt = DateTime::createFromFormat('U.u', number_format($process->getStartTime(), 6, '.', ''));
        $startedAt->setTimezone(new DateTimeZone('Europe/Paris'));

        return new static($process->getPid(), $process->getCommandLine(), $startedAt);
    }
}
