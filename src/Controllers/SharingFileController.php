<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Utils\FileManager;
use Symfony\Component\HttpFoundation\Request;

class SharingFileController extends AbstractFileController
{
    protected function getArguments(Request $request)
    {
        $arguments = parent::getArguments($request);

        $sharing = Sharing::loadByToken($request->attributes->get('token'));

        if (!$sharing) {
            $this->abort(404, 'error.sharingNotFound');
        }

        array_unshift($arguments, FileManager::getBySharing($this->getUserId(), $sharing));

        return $arguments;
    }

    public function getRouteParameters($action)
    {
        global $app;

        $parameters = parent::getRouteParameters($action);

        $parameters['token'] = $app['request_stack']->getCurrentRequest()->attributes->get('token');

        return $parameters;
    }

    protected function getFileManager($request)
    {
        $sharing = Sharing::loadByToken($request->attributes->get('token'));
        return FileManager::getBySharing($this->getUserId(), $sharing);
    }
}
