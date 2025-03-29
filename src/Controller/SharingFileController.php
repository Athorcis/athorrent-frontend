<?php

namespace Athorrent\Controller;

use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/sharings/{token}/files', name: 'sharings')]
class SharingFileController extends AbstractFileController
{
}
