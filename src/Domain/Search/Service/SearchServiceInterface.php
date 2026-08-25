<?php

namespace App\Domain\Search\Service;

interface SearchServiceInterface
{
    public function search(string $searchStr): string;
}
