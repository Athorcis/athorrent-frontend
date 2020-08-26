<?php

namespace Athorrent\View;

use Athorrent\Filesystem\UserFilesystemEntry;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigHelperExtension extends AbstractExtension
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('torrentStateToClass', [$this, 'torrentStateToClass']),
            new TwigFunction('format_age', [$this, 'formatAge']),
            new TwigFunction('icon', [$this, 'getIcon']),
            new TwigFunction('base64_encode', 'base64_encode'),
            new TwigFunction('format_bytes', [$this, 'formatBytes'])
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

    public function torrentStateToClass($torrent): string
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

    public function formatAge($age)
    {
        $steps = [
            'seconds' => 60,
            'minutes' => 3600,
            'hours' => 86400,
            'days' => 2592000,
            'months' => 31557600,
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

    public function formatBytes($value): string
    {
        return \ByteUnits\Metric::bytes($value)->format(null, ' ');
    }
}
