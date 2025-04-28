<?php

namespace Athorrent\View;

use Athorrent\Cache\KeyGenerator\LocalizedKeyGenerator;
use Athorrent\Filesystem\UserFilesystemEntry;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigHelperExtension extends AbstractExtension
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly LocalizedKeyGenerator $keyGenerator)
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
            new TwigFunction('format_bytes', $this->formatBytes(...)),
            new TwigFunction('cache_key', $this->getCacheKey(...)),
            new TwigFunction('sha256', $this->hashWithSha256(...)),
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

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        static $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        static $maxExponent = count($units) - 1;

        $base = 1024;

        if ($bytes === 0) {
            $value = 0;
            $exponent = 0;
        }
        else {
            $exponent = floor(log($bytes) / log($base));
            $exponent = min($exponent, $maxExponent);

            $value = round($bytes / pow($base, $exponent), $precision);
        }

        return $value . ' ' . $units[$exponent];
    }

    public function getCacheKey(string $annotation, mixed $value = null): string
    {
        return $annotation . '.' . $this->keyGenerator->generateKey($value);
    }

    public function hashWithSha256(string $value): string
    {
        return hash('sha256', $value);
    }
}
