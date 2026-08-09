<?php

declare(strict_types=1);

namespace Athorrent\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Same-origin helpers inspired by Symfony's SameOriginCsrfTokenManager::isValidOrigin().
 */
final class SameOrigin
{
    public static function matches(Request $request, string $url): bool
    {
        return str_starts_with($url . '/', $request->getSchemeAndHttpHost() . '/');
    }

    public static function getSameOriginReferer(Request $request): ?string
    {
        $referer = $request->headers->get('Referer');

        if ($referer === null || !self::matches($request, $referer)) {
            return null;
        }

        return $referer;
    }

    /**
     * Whether the incoming request is same-origin.
     */
    public static function isSameOrigin(Request $request): bool
    {
        if (null !== $header = $request->headers->get('Sec-Fetch-Site')) {
            return 'same-origin' === $header;
        }

        foreach (['Origin', 'Referer'] as $header) {
            if (!$request->headers->has($header)) {
                continue;
            }

            $source = $request->headers->get($header);

            if ($source !== null && self::matches($request, $source)) {
                return true;
            }
        }

        return false;
    }
}
