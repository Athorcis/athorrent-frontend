<?php

declare(strict_types=1);

namespace Athorrent\View;

use Athorrent\Cache\KeyGenerator\LocalizedKeyGenerator;
use Athorrent\Filesystem\UserFilesystemEntry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigHelperExtension extends AbstractExtension
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LocalizedKeyGenerator $keyGenerator,
        private readonly AccessMapInterface $accessMap,
        private readonly RequestStack $requestStack,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('format_age', $this->formatAge(...)),
            new TwigFunction('date_to_age', $this->dateToAge(...)),
            new TwigFunction('icon', $this->getIcon(...)),
            new TwigFunction('base64_encode', 'base64_encode'),
            new TwigFunction('format_bytes', $this->formatBytes(...)),
            new TwigFunction('cache_key', $this->getCacheKey(...)),
            new TwigFunction('sha256', $this->hashWithSha256(...)),
            new TwigFunction('is_auth_required', $this->isAuthRequired(...)),
        ];
    }

    public function getIcon($value): string
    {
        if ($value instanceof UserFilesystemEntry) {
            if ($value->isDirectory()) {
                return 'directory';
            } elseif ($value->isText()) {
                return 'text-file';
            } elseif ($value->isImage()) {
                return 'image-file';
            } elseif ($value->isPlayable()) {
                return 'playable-file';
            }

            return 'file';
        }

        return '';
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

    public function isAuthRequired(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        [$roles] = $this->accessMap->getPatterns($request);

        return is_array($roles) && !in_array('PUBLIC_ACCESS', $roles, true);
    }
}
