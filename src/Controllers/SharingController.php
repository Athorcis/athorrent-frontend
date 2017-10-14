<?php

namespace Athorrent\Controllers;

use Athorrent\Entity\Sharing;
use Athorrent\Utils\FileManager;
use Symfony\Component\HttpFoundation\Request;

class SharingController extends AbstractController
{
    protected function getRouteDescriptors()
    {
        return [
            ['GET', '/', 'listSharings'],

            ['POST', '/', 'addSharing', 'ajax'],
            ['POST', '/{token}', 'removeSharing', 'ajax']
        ];
    }

    public function listSharings(Request $request)
    {
        if ($request->query->has('page')) {
            $page = $request->query->get('page');

            if (!is_numeric($page) || $page < 1) {
                $app->abort(400);
            }
        } else {
            $page = 1;
        }

        $offset = 10 * ($page - 1);

        $sharings = Sharing::loadByUserId($this->getUserId(), $offset, 10, $total);

        if ($offset >= $total && $total > 0) {
            $this->abort(404);
        }

        $lastPage = ceil($total / 10);

        return $this->render(
            array (
            'sharings' => $sharings,
            'page' => $page,
            'lastPage' => $lastPage
            )
        );
    }

    public function addSharing(Request $request)
    {
        if (!$request->request->has('path')) {
            return $this->abort(400);
        }

        $fileManager = FileManager::getByUser($this->getUserId());
        $path = $fileManager->getAbsolutePath($request->request->get('path'));

        if (!file_exists($path)) {
            return $this->abort(404);
        }

        $sharing = new Sharing(null, $this->getUserId(), $fileManager->getRelativePath($path));
        $sharing->save();

        return $this->success($this->url('listFiles', ['token' => $sharing->getToken(), '_prefixId' => 'sharings']));
    }

    public function removeSharing(Request $request, $token)
    {
        if (!Sharing::deleteByToken($token, $this->getUserId())) {
            return $this->abort(404, 'error.sharingNotFound');
        }

        return $this->success();
    }
}
