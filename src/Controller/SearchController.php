<?php

namespace Athorrent\Controller;

use Athorrent\Utils\Search\TorrentSearcher;
use Athorrent\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/search", name="search")
 */
class SearchController
{
    /**
     * @Route("/", methods="GET")
     *
     * @param Request $request
     * @return View
     */
    public function showSearch(Request $request): View
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
