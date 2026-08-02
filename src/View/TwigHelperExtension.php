<?php

declare(strict_types=1);

namespace Athorrent\View;

use Athorrent\Cache\KeyGenerator\CacheKeyGetterInterface;
use Athorrent\Cache\KeyGenerator\LocalizedKeyGenerator;
use Athorrent\Filesystem\UserFilesystemEntry;
use NumberFormatter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\UX\Icons\IconRendererInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigHelperExtension extends AbstractExtension
{
    /** @var array<string, string> */
    private array $iconCache = [];

    /** @var array<string, NumberFormatter> */
    private array $byteFormatters = [];

    public function __construct(
        private readonly LocalizedKeyGenerator $keyGenerator,
        private readonly AccessMapInterface $accessMap,
        private readonly RequestStack $requestStack,
        private readonly IconRendererInterface $iconRenderer,
        #[Autowire('%kernel.default_locale%')]
        private readonly string $defaultLocale,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('date_to_age', $this->dateToAge(...)),
            new TwigFunction('file_icon', $this->getFileIcon(...)),
            new TwigFunction('icon', $this->renderIcon(...), ['is_safe' => ['html']]),
            new TwigFunction('base64_encode', 'base64_encode'),
            new TwigFunction('format_bytes', $this->formatBytes(...)),
            new TwigFunction('cache_key', $this->getCacheKey(...)),
            new TwigFunction('sha256', $this->hashWithSha256(...)),
            new TwigFunction('is_auth_required', $this->isAuthRequired(...)),
            new TwigFunction('get_route_cache_key', $this->getRouteCacheKey(...)),
        ];
    }

    /**
     * @param array<string, bool|string> $attributes
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        $cacheKey = $name . "\0" . json_encode($attributes, JSON_THROW_ON_ERROR);

        return $this->iconCache[$cacheKey] ??= $this->iconRenderer->renderIcon($name, $attributes);
    }

    public function getFileIcon(UserFilesystemEntry $value): string
    {
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

            $value = $bytes / ($base ** $exponent);
        }

        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->defaultLocale;
        $cacheKey = $locale . "\0" . $precision;

        $formatter = $this->byteFormatters[$cacheKey] ??= $this->createByteFormatter($locale, $precision);

        return $formatter->format($value) . ' ' . $units[$exponent];
    }

    private function createByteFormatter(string $locale, int $precision): NumberFormatter
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return $formatter;
    }

    /**
     * @param CacheKeyGetterInterface|list<string>|string|null $value
     */
    public function getCacheKey(string $annotation, CacheKeyGetterInterface|array|string|null $value = null): string
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

        if ($request === null) {
            return false;
        }

        [$roles] = $this->accessMap->getPatterns($request);

        return is_array($roles) && !in_array('PUBLIC_ACCESS', $roles, true);
    }

    public function getRouteCacheKey(Request $request, ?string $error = null): string
    {
        $attributes = $request->attributes;

        if ($error) {
            return 'error';
        }

        $route = $attributes->get('_route');
        $subroute = $attributes->get('_subroute');

        $cacheKey = $route;

        if ($subroute) {
            $cacheKey .= '_' . $subroute;
        }

        return $cacheKey;
    }
}
