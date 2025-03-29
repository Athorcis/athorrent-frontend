<?php

namespace Athorrent\Controller;

use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/user/files', name: 'files')]
class FileController extends AbstractFileController
{
}
