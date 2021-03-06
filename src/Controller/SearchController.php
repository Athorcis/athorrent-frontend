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
    public function showSearch(Request $request, TorrentSearcher $searcher): View
    {
        $query = $request->query->get('q');
        $source = $request->query->get('source');

        $sources = array_map(function ($source) {
            return $source->getName();
        }, $searcher->getSources());

        if (empty($query)) {
            $results = [];
        } else {
            $results = $searcher->search($query, $source === 'all' ? null: $source);
        }

        return new View([
            'query' => $query,
            'source' => $source,
            'sources' => $sources,
            'resultsMap' => $results
        ], 'showSearch');
    }
}
