<?php

namespace Athorrent\Controller;

use Athorrent\Backend\BackendFactory;
use Athorrent\Database\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QBittorrentProxyController extends AbstractController
{
    public function __construct(
        private readonly BackendFactory $backendFactory,
    ) {}

    #[Route('/user/qb/{path}', requirements: ['path' => '.*'], methods: ['GET','POST','PUT','PATCH','DELETE'], options: ['csrf' => false])]
    public function proxyToQBittorrent(Request $request, string $path, UrlGeneratorInterface $urlGenerator): Response
    {
        $user = $this->getUser();

        if (!($user instanceof User) || $user->getClientType() !== User::CLIENT_TYPE_QBITTORRENT) {
            throw new AccessDeniedHttpException('unsupported client type');
        }

        $backend = $this->backendFactory->create($user);
        $blocked = ['api/v2/auth/login'];

        if (in_array($path, $blocked, true)) {
            return new Response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $body = $this->resolveProxyBody($request);
        $headers = $this->extractForwardedHeaders($request);
        if (is_array($body)) {
            $headers = $this->withoutContentTypeHeader($headers);
        }

        $qbResponse = $backend->request($request->getMethod(), '/' . ltrim($path, '/'), [
            'headers' => $headers,
            'body' => $body,
            'query' => $request->query->all(),
        ]);
        $content = $qbResponse->getContent();
        $status = $qbResponse->getStatusCode();
        $respHeaders = $qbResponse->getHeaders(false);

        $content = str_replace(
            '<head>',
            '<head><base href="' . $urlGenerator->generate('proxyToQBittorrent') . '/">' . ($_ENV['ANALYTICS_TAG'] ?? ''),
            $content,
        );


        $response = new Response($content, $status);
        foreach ($respHeaders as $name => $values) {
            if (in_array(strtolower($name), ['set-cookie', 'content-length', 'date'])) {
                continue; // ne pas renvoyer cookie qB au navigateur
            }
            foreach ($values as $value) {
                $response->headers->set($name, $value, false);
            }
        }
        return $response;
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
     * @param UploadedFile|array<UploadedFile|array> $file - Fichier(s) Symfony
     * @return resource|array<int|string, mixed>
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
     * Retire Content-Type pour laisser HttpClient fixer boundary / encoding.
     *
     * @param array<string, string> $headers - En-têtes à filtrer
     * @return array<string, string>
     */
    private function withoutContentTypeHeader(array $headers): array
    {
        foreach (array_keys($headers) as $name) {
            if (strtolower($name) === 'content-type') {
                unset($headers[$name]);
            }
        }

        return $headers;
    }
}
