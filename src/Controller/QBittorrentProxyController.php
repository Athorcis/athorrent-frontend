<?php

declare(strict_types=1);

namespace Athorrent\Controller;

use Athorrent\Backend\BackendFactory;
use Athorrent\Backend\QBittorrentBackend;
use Athorrent\Database\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class QBittorrentProxyController extends AbstractController
{
    public function __construct(
        private readonly BackendFactory $backendFactory,
        private readonly HttpClientInterface $http,
    ) {}

    #[Route('/user/qb/{path}', requirements: ['path' => '.*'], methods: ['GET','POST','PUT','PATCH','DELETE'], options: ['csrf' => false])]
    public function proxyToQBittorrent(Request $request, string $path, UrlGeneratorInterface $urlGenerator): Response
    {
        if (!$request->isMethodSafe() && true !== $this->isSameOrigin($request)) {
            throw new AccessDeniedHttpException();
        }

        $blocked = ['api/v2/auth/login'];

        if (in_array($path, $blocked, true)) {
            return new Response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        /** @var User $user */
        $user = $this->getUser();

        /** @var QBittorrentBackend $backend */
        $backend = $this->backendFactory->create($user);

        $body = $this->resolveProxyBody($request);
        $headers = $this->extractForwardedHeaders($request);

        $headersToRemove = ['accept-encoding'];

        if (is_array($body)) {
            $headersToRemove[] = 'content-type';
        }

        $headers = $this->removeHeaders($headers, $headersToRemove);

        $qbResponse = $backend->request($request->getMethod(), '/' . ltrim($path, '/'), [
            'headers' => $headers,
            'body' => $body,
            'query' => $request->query->all(),
            // Buffer only HTML (needs <base> rewrite); stream assets/API/downloads.
            'buffer' => static function (array $headers): bool {
                $contentType = $headers['content-type'][0] ?? '';

                return str_starts_with($contentType, 'text/html');
            },
        ]);

        $status = $qbResponse->getStatusCode();
        $respHeaders = $qbResponse->getHeaders(false);
        $contentType = $respHeaders['content-type'][0] ?? '';

        if (str_starts_with($contentType, 'text/html')) {
            $response = $this->createHtmlProxyResponse($qbResponse, $status, $urlGenerator);
        } else {
            $response = $this->createStreamedProxyResponse($qbResponse, $status);
        }

        $this->copyUpstreamHeaders($respHeaders, $response);

        return $response;
    }

    private function createHtmlProxyResponse(
        ResponseInterface $qbResponse,
        int $status,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $content = $qbResponse->getContent();

        if (str_contains($content, '<!DOCTYPE html>')) {
            $newLine = "\n    ";
            $content = str_replace(
                '<head>',
                '<head>' . $newLine .
                '<base href="' . $urlGenerator->generate('proxyToQBittorrent') . '">' .
                ($_ENV['ANALYTICS_TAG'] ?? ''),
                $content,
            );
        }

        return new Response($content, $status);
    }

    private function createStreamedProxyResponse(ResponseInterface $qbResponse, int $status): StreamedResponse
    {
        return new StreamedResponse(function () use ($qbResponse): void {
            foreach ($this->http->stream($qbResponse) as $chunk) {
                echo $chunk->getContent();
            }
        }, $status);
    }

    /**
     * @param array<string, string[]> $respHeaders
     */
    private function copyUpstreamHeaders(array $respHeaders, Response $response): void
    {
        foreach ($respHeaders as $name => $values) {
            // Despite not returning gzip content-encoding is still set with gzip value
            if (in_array(strtolower($name), ['set-cookie', 'content-encoding', 'content-length', 'date'], true)) {
                continue; // ne pas renvoyer cookie qB au navigateur
            }

            foreach ($values as $value) {
                $response->headers->set($name, $value, false);
            }
        }
    }

    /**
     * Copied from Symfony\Component\Security\Csrf\SameOriginCsrfTokenManager::isValidOrigin().
     *
     * @return bool Whether the origin is valid
     */
    private function isSameOrigin(Request $request): bool
    {
        if (null !== $header = $request->headers->get('Sec-Fetch-Site')) {
            return 'same-origin' === $header;
        }

        $target = $request->getSchemeAndHttpHost().'/';

        foreach (['Origin', 'Referer'] as $header) {
            if (!$request->headers->has($header)) {
                continue;
            }
            $source = $request->headers->get($header);

            if (str_starts_with($source.'/', $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Corps à transmettre : brut si disponible, sinon champs/fichiers parsés.
     *
     * @param Request $request - Requête entrante du navigateur
     * @return array<string, mixed>|string
     */
    private function resolveProxyBody(Request $request): array|string
    {
        if ($this->isParsedFormRequest($request)) {
            return $this->buildFormBody($request);
        }

        return $request->getContent();
    }

    /**
     * Détecte une requête formulaire dont le corps brut n'est plus lisible.
     *
     * @param Request $request - Requête entrante
     * @return bool
     */
    private function isParsedFormRequest(Request $request): bool
    {
        if ($request->files->count() > 0) {
            return true;
        }

        $contentType = $request->headers->get('Content-Type', '');
        if (str_starts_with($contentType, 'multipart/form-data')) {
            return true;
        }

        return $request->request->count() > 0
            && $request->getContent() === '';
    }

    /**
     * Reconstruit le corps pour HttpClient (urlencoded ou multipart).
     *
     * @param Request $request - Requête entrante
     * @return array<string, mixed>
     */
    private function buildFormBody(Request $request): array
    {
        $body = $request->request->all();
        foreach ($request->files->all() as $key => $file) {
            $body[$key] = $this->mapUploadedFiles($file);
        }

        return $body;
    }

    /**
     * Ouvre un fichier uploadé (ou une liste) pour l'envoi multipart.
     *
     * @param UploadedFile|array<mixed> $file - Fichier(s) Symfony
     * @return resource|false|array<mixed>
     */
    private function mapUploadedFiles(UploadedFile|array $file): mixed
    {
        if (is_array($file)) {
            $mapped = [];
            foreach ($file as $key => $item) {
                $mapped[$key] = $this->mapUploadedFiles($item);
            }

            return $mapped;
        }

        return fopen($file->getPathname(), 'r');
    }

    /**
     * En-têtes HTTP à relayer vers qBittorrent (hors host, cookie, length).
     *
     * @param Request $request - Requête entrante
     * @return array<string, string>
     */
    private function extractForwardedHeaders(Request $request): array
    {
        $skip = ['host', 'cookie', 'content-length'];
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            if (in_array(strtolower($name), $skip, true)) {
                continue;
            }
            $headers[$name] = implode(', ', $values);
        }

        return $headers;
    }

    /**
     * @param array<string, string> $headers - En-têtes à filtrer
     * @param string[] $toRemove
     * @return array<string, string>
     */
    private function removeHeaders(array $headers, array $toRemove): array
    {
        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), $toRemove, true)) {
                unset($headers[$name]);
            }
        }

        return $headers;
    }
}
