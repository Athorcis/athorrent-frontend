<?php

declare(strict_types=1);

namespace Athorrent\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route(
    path: '/shared/{id}/files',
    name: 'sharedFiles_',
    requirements: ['id' => Requirement::UUID],
)]
class SharingFileController extends AbstractFileController
{
}
