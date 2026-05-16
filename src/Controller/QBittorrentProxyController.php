<?php

namespace Athorrent\Controller;

use Athorrent\Backend\BackendFactory;
use Athorrent\Database\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            if (in_array(strtolower($name), ['host', 'cookie', 'content-length'], true)) {
                continue;
            }
            $headers[$name] = implode(', ', $values);
        }

        $qbResponse = $backend->request($request->getMethod(), '/' . ltrim($path, '/'), [
            'headers' => $headers,
            'body' => $request->getContent(),
            'query' => $request->query->all()
        ]);
        $content = $qbResponse->getContent();
        $status = $qbResponse->getStatusCode();
        $respHeaders = $qbResponse->getHeaders(false);

        $content = str_replace('<head>', '<head><base href="' . $urlGenerator->generate('proxyToQBittorrent') . '/">', $content);


        $response = new Response($content, $status);
        foreach ($respHeaders as $name => $values) {
            if (strtolower($name) === 'set-cookie' || strtolower($name) === 'content-length') {
                continue; // ne pas renvoyer cookie qB au navigateur
            }
            foreach ($values as $value) {
                $response->headers->set($name, $value, false);
            }
        }
        return $response;
    }
}
