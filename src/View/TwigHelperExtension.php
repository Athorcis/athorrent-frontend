<?php

namespace Athorrent\View;

use Athorrent\Filesystem\UserFilesystemEntry;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;

class TwigHelperExtension extends AbstractExtension
{
    private $publicDir;

    private $translator;

    private $manifest;

    public function __construct(string $publicDir, TranslatorInterface $translator)
    {
        $this->publicDir = $publicDir;
        $this->translator = $translator;
        $this->manifest = json_decode(file_get_contents($publicDir . '/manifest.json'), true);
    }

    public function getFunctions()
    {
        return [
            new \Twig_Function('torrentStateToClass', [$this, 'torrentStateToClass']),
            new \Twig_Function('asset_path', [$this, 'getAssetPath']),
            new \Twig_Function('asset_url', [$this, 'getAssetUrl']),
            new \Twig_Function('stylesheet', [$this, 'includeStylesheet']),
            new \Twig_Function('script', [$this, 'includeScript']),
            new \Twig_Function('format_age', [$this, 'formatAge']),
            new \Twig_Function('icon', [$this, 'getIcon']),
            new \Twig_Function('base64_encode', 'base64_encode'),
            new \Twig_Function('format_bytes', [$this, 'formatBytes'])
        ];
    }

    public function getIcon($value)
    {
        if ($value instanceof UserFilesystemEntry) {
            if ($value->isDirectory()) {
                return 'fa-folder-open-o';
            } elseif ($value->isText()) {
                return 'fa-file-text-o';
            } elseif ($value->isImage()) {
                return 'fa-file-image-o';
            } elseif ($value->isAudio()) {
                return 'fa-file-audio-o';
            } elseif ($value->isVideo()) {
                return 'fa-file-video-o';
            } elseif ($value->isPdf()) {
                return 'fa-file-pdf-o';
            } elseif ($value->isArchive()) {
                return 'fa-file-archive-o';
            }

            return 'fa-file-o';
        }

        return '';
    }

    public function torrentStateToClass($torrent)
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

    public function getAssetPath($assetId)
    {
        if (isset($this->manifest[$assetId])) {
            return $this->manifest[$assetId];
        }

        return '/' . $assetId;
    }

    public function getAssetUrl($assetId)
    {
        return '//' . $_ENV['STATIC_HOST'] . $this->getAssetPath($assetId);
    }

    protected function includeResource($assetId, $inline)
    {
        $relativePath = $this->getAssetPath($assetId);
        $absolutePath = $this->publicDir . $relativePath;

        if ($_ENV['APP_ENV'] !== 'dev' && ($inline === true || ($inline === null && filesize($absolutePath) < 1024))) {
            return ['content' => file_get_contents($absolutePath)];
        }

        return ['path' => '//' . $_ENV['STATIC_HOST'] . $relativePath];
    }

    public function includeStylesheet($path, $inline = false)
    {
        $result = $this->includeResource('stylesheets/' . $path . '.css', $inline);

        if (isset($result['content'])) {
            return '<style type="text/css">' . $result['content'] . '</style>';
        }

        return '<link rel="stylesheet" type="text/css" href="' . $result['path'] . '" />';
    }

    public function includeScript($path, $inline = false)
    {
        $result = $this->includeResource('scripts/' . $path . '.js', $inline);

        if (isset($result['content'])) {
            return '<script type="text/javascript">' . $result['content'] . '</script>';
        }

        return '<script type="text/javascript" src="' . $result['path'] . '"></script>';
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
                return $n . ' ' . $this->translator->transChoice('search.age.' . $magnitude, $n);
            }

            $previousLimit = $limit;
        }
    }

    public function formatBytes($value)
    {
        return \ByteUnits\Metric::bytes($value)->format();
    }
}
