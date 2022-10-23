<?php

namespace Athorrent\Controller;

use Athorrent\Utils\Search\JackettApi;
use Athorrent\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

#[Route(path: '/search', name: 'search')]
class SearchController extends AbstractController
{
    #[Route(path: '/', methods: 'GET')]
    public function showSearch(Request $request, JackettApi $jackett, CacheInterface $cache): View
    {
        $query = $request->query->get('q');
        $source = $request->query->get('source');

        $sources = $cache->get('search.trackers', fn () => $jackett->getConfiguredIndexers());

        if (empty($query)) {
            $results = [];
        } else {
            $results = $jackett->getResults($query, $source);
        }

        return new View([
            'query' => $query,
            'source' => $source,
            'sources' => $sources,
            'results' => $results
        ], 'showSearch');
    }
}
