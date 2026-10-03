<?php

declare(strict_types=1);

namespace Athorrent\Controller;

use Nelmio\SecurityBundle\Controller\ContentSecurityPolicyController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContentSecurityPolicyReportController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'nelmio_security.csp_reporter_controller')]
        private readonly ContentSecurityPolicyController $reporter,
    ) {
    }

    #[Route(path: '/nelmio/csp/report', name: 'nelmio_security_csp_report', methods: ['POST'], options: ['csrf' => false])]
    public function report(Request $request): Response
    {
        return $this->reporter->indexAction($request);
    }
}
