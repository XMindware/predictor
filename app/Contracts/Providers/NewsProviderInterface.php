<?php

namespace App\Contracts\Providers;

use App\Data\Providers\NewsData;

interface NewsProviderInterface
{
    /**
     * Fetch normalized news items for the supplied criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @return list<NewsData>
     */
    public function fetchNews(array $criteria = []): array;
}
