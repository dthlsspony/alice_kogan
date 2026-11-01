<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Результат универсального поиска: страница смешанных результатов
 * и количество совпадений по каждому типу.
 */
class SearchResult
{
    /**
     * @param  array<string, int>  $counts
     */
    public function __construct(
        public readonly LengthAwarePaginator $paginator,
        public readonly array $counts = [],
    ) {}
}
