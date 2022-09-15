<?php

namespace Athorrent\Controller;

use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/sharings/{token}/files', name: 'sharings')]
class SharingFileController extends AbstractFileController
{
}
