<?php

namespace Athorrent\View;

use Athorrent\Filesystem\UserFilesystemEntry;
use Athorrent\Security\Nonce\NonceManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigHelperExtension extends AbstractExtension
{
    private $publicDir;

    private $translator;

    private $nonceManager;

    private $manifest;

    public function __construct(string $publicDir, TranslatorInterface $translator, NonceManager $nonceManager)
    {
        $this->publicDir = $publicDir;
        $this->translator = $translator;
        $this->nonceManager = $nonceManager;
        $this->manifest = json_decode(file_get_contents($publicDir.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('torrentStateToClass', [$this, 'torrentStateToClass']),
            new TwigFunction('asset_path', [$this, 'getAssetPath']),
            new TwigFunction('asset_url', [$this, 'getAssetUrl']),
            new TwigFunction('stylesheet', [$this, 'includeStylesheet']),
            new TwigFunction('script', [$this, 'includeScript']),
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

    public function getAssetPath($assetId)
    {
        return $this->manifest[$assetId] ?? ('/'.$assetId);
    }

    public function getAssetUrl($assetId): string
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

    public function includeStylesheet($path, $inline = false): string
    {
        $result = $this->includeResource('stylesheets/' . $path . '.css', $inline);

        if (isset($result['content'])) {
            return '<style type="text/css">' . $result['content'] . '</style>';
        }

        return '<link rel="stylesheet" type="text/css" href="' . $result['path'] . '" />';
    }

    public function includeScript($path, $inline = false): string
    {
        $result = $this->includeResource('scripts/' . $path . '.js', $inline);

        if (isset($result['content'])) {
            return '<script type="text/javascript" nonce="' . $this->nonceManager->getNonce() . '">' . $result['content'] . '</script>';
        }

        return '<script type="text/javascript" src="' . $result['path'] . '" nonce="' . $this->nonceManager->getNonce() . '"></script>';
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
        return \ByteUnits\Metric::bytes($value)->format();
    }
}
