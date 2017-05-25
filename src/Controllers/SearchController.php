<?php

namespace Athorrent\Controllers;

use Athorrent\Utils\Search\TorrentSearcher;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends AbstractController
{
    protected static $actionPrefix = 'search_';

    protected static $routePattern = '/search';

    protected static function buildRoutes()
    {
        $routes = parent::buildRoutes();

        $routes[] = ['GET', '/', 'showSearch'];

        return $routes;
    }

    protected function showSearch(Request $request)
    {
        $query = $request->query->get('q');
        $source = $request->query->get('source');

        $sources = [
            'tpb' => 'The Pirate Bay',
            'nyaa' => 'Nyaa Torrents',
            'anidex' => 'AniDex'
        ];

        if (empty($query)) {
            $results = [];
        } else {
            $searcher = new TorrentSearcher();
            $results = $searcher->search($query, $source);
        }

        return $this->render([
            'query' => $query,
            'source' => $source,
            'sources' => $sources,
            'resultsMap' => $results
        ], 'showSearch');
    }
}
