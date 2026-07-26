<?php

declare(strict_types=1);

namespace Athorrent\Utils;

/**
 * Parses, merges, and serializes Content-Security-Policy header values.
 *
 * Source-list directives are unioned; flag directives (no value) are kept if
 * present on either side. Existing source order is preserved; new sources are
 * appended.
 *
 * When the overlay introduces a fetch directive that was absent from the base
 * policy, the merge seeds it with the sources that were effectively in force
 * via CSP fallbacks (e.g. default-src), so specializing a directive does not
 * accidentally drop 'self' or other fallback sources.
 */
final readonly class ContentSecurityPolicy
{
    /**
     * Fetch directives that fall back to default-src when omitted.
     *
     * @var list<string>
     */
    private const array DEFAULT_SRC_FALLBACKS = [
        'child-src',
        'connect-src',
        'fenced-frame-src',
        'font-src',
        'frame-src',
        'img-src',
        'manifest-src',
        'media-src',
        'object-src',
        'prefetch-src',
        'script-src',
        'script-src-attr',
        'script-src-elem',
        'style-src',
        'style-src-attr',
        'style-src-elem',
        'worker-src',
    ];

    /**
     * @param array<string, list<string>|true> $directives
     */
    private function __construct(
        private array $directives,
    ) {
    }

    public static function parse(string $header): self
    {
        $directives = [];

        foreach (explode(';', $header) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $tokens = preg_split('/\s+/', $part);
            if ($tokens === false) {
                continue;
            }

            $name = array_shift($tokens);
            if ($name === '') {
                continue;
            }

            if ($tokens === []) {
                $directives[$name] = true;
                continue;
            }

            $existing = \is_array($directives[$name] ?? null) ? $directives[$name] : [];
            $directives[$name] = array_values(array_unique([...$existing, ...$tokens]));
        }

        return new self($directives);
    }

    public static function mergeHeaders(string $base, string $overlay): string
    {
        return self::parse($base)->merge(self::parse($overlay))->toString();
    }

    public function merge(self $other): self
    {
        $merged = $this->directives;

        foreach ($other->directives as $name => $value) {
            if ($value === true) {
                if (!\array_key_exists($name, $merged)) {
                    $merged[$name] = true;
                }
                continue;
            }

            if (\array_key_exists($name, $merged) && \is_array($merged[$name])) {
                $existing = $merged[$name];
            } elseif (\array_key_exists($name, $merged)) {
                $existing = [];
            } else {
                $existing = $this->effectiveFallbackSources($name);
            }

            $merged[$name] = array_values(array_unique([...$existing, ...$value]));
        }

        return new self($merged);
    }

    public function withSources(string $directive, string ...$sources): self
    {
        if ($sources === []) {
            return $this;
        }

        return $this->merge(new self([$directive => array_values($sources)]));
    }

    public function toString(): string
    {
        $parts = [];

        foreach ($this->directives as $name => $value) {
            $parts[] = $value === true
                ? $name
                : $name . ' ' . implode(' ', $value);
        }

        return implode('; ', $parts);
    }

    /**
     * Sources that currently apply to $directive on this policy via CSP fallbacks.
     *
     * @return list<string>
     */
    private function effectiveFallbackSources(string $directive): array
    {
        $chain = match ($directive) {
            'script-src-elem', 'script-src-attr' => ['script-src', 'default-src'],
            'style-src-elem', 'style-src-attr' => ['style-src', 'default-src'],
            'frame-src', 'worker-src' => ['child-src', 'default-src'],
            'fenced-frame-src' => ['frame-src', 'child-src', 'defaulf-src'],
            default => \in_array($directive, self::DEFAULT_SRC_FALLBACKS, true)
                ? ['default-src']
                : [],
        };

        foreach ($chain as $fallback) {
            $value = $this->directives[$fallback] ?? null;
            if (\is_array($value)) {
                return $value;
            }
        }

        return [];
    }
}
