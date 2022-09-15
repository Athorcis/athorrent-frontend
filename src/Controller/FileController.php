<?php

namespace Athorrent\Controller;

use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/user/files', name: 'files')]
class FileController extends AbstractFileController
{
}
