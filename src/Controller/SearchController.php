<?php

namespace Athorrent\Controller;

use Athorrent\Utils\Search\JackettApi;
use Athorrent\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

#[Route(path: '/search', name: 'search')]
class SearchController extends AbstractController
{
    #[Route(path: '/', methods: 'GET')]
    public function showSearch(
        JackettApi $jackett,
        CacheInterface $cache,
        #[MapQueryParameter(name: 'q')] string $query = '',
        #[MapQueryParameter] string $source = 'all',
    ): View
    {
        $sources = $cache->get('search.trackers', fn () => $jackett->getConfiguredIndexers());

        if ($query === '') {
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
