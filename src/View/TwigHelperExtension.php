<?php

namespace Athorrent\View;

use Athorrent\Filesystem\UserFilesystemEntry;
use ByteUnits\Metric;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigHelperExtension extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('torrentStateToClass', $this->torrentStateToClass(...)),
            new TwigFunction('format_age', $this->formatAge(...)),
            new TwigFunction('date_to_age', $this->dateToAge(...)),
            new TwigFunction('icon', $this->getIcon(...)),
            new TwigFunction('base64_encode', 'base64_encode'),
            new TwigFunction('format_bytes', $this->formatBytes(...))
        ];
    }

    public function getIcon($value): string
    {
        if ($value instanceof UserFilesystemEntry) {
            if ($value->isDirectory()) {
                return 'fa-folder-open';
            } elseif ($value->isText()) {
                return 'fa-file-alt';
            } elseif ($value->isImage()) {
                return 'fa-file-image';
            } elseif ($value->isAudio()) {
                return 'fa-file-audio';
            } elseif ($value->isVideo()) {
                return 'fa-file-video';
            } elseif ($value->isPdf()) {
                return 'fa-file-pdf';
            } elseif ($value->isArchive()) {
                return 'fa-file-archive';
            }

            return 'fa-file';
        }

        return '';
    }

    public function torrentStateToClass(array $torrent): string
    {
        $state = $torrent['state'];

        if ($state === 'paused') {
            $class = 'warning';
        } elseif ($state === 'seeding' || $state === 'downloading') {
            $class = 'success';
        } elseif ($state === 'disabled') {
            $class = 'disabled';
        } else {
            $class = 'info';
        }

        return $class;
    }

    public function formatAge(int $age): ?string
    {
        $steps = [
            'seconds' => 60,
            'minutes' => 3600,
            'hours' => 86400,
            'days' => 2_592_000,
            'months' => 31_557_600,
            'years' => INF
        ];

        $previousLimit = 1;

        foreach ($steps as $magnitude => $limit) {
            if ($age < $limit) {
                $n = floor($age / $previousLimit);
                return $n . ' ' . $this->translator->trans('search.age.' . $magnitude, ['count' => $n]);
            }

            $previousLimit = $limit;
        }

        return null;
    }

    public function dateToAge(string $date): int
    {
        return time() - strtotime($date);
    }

    public function formatBytes(int $value): string
    {
        return Metric::bytes($value)->format(null, ' ');
    }
}
