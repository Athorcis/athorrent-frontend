<?php

namespace Athorrent\Controller;

use Athorrent\Routing\AbstractController;
use Athorrent\Utils\Search\TorrentSearcher;
use Athorrent\View\View;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends AbstractController
{
    public function getRouteDescriptors()
    {
        return [['GET', '/', 'showSearch']];
    }

    public function showSearch(Request $request)
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

        return new View([
            'query' => $query,
            'source' => $source,
            'sources' => $sources,
            'resultsMap' => $results
        ], 'showSearch');
    }
}
