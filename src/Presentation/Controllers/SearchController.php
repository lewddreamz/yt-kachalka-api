<?php

namespace App\Presentation\Controllers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class SearchController
{
    public function __construct(public SearchServiceInterface $SearchService)
    {
    }
    /**
     * @param string $search
     */
    #[Route(path: '/search', name: 'search')]
    public function search(
        #[MapQueryParameter(filter: FILTER_SANITIZE_STRING)] string $search
    ): Response
    {
        
    }
}
