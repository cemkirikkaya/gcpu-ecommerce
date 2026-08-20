<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
    /**
     * @return Collection<int, Product>
     */
    public function suggest(string $query, int $limit = 8): Collection
    {
        $normalized = $this->normalizeTerm($query);

        if ($normalized === null) {
            return new Collection;
        }

        $likeTerm = '%'.$this->escapeLike($normalized).'%';
        $prefixTerm = $this->escapeLike($normalized).'%';

        return Product::query()
            ->with(['category', 'images', 'media'])
            ->where(function ($builder) use ($likeTerm): void {
                $builder
                    ->whereRaw('LOWER(name) LIKE ?', [$likeTerm])
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->whereRaw('LOWER(name) LIKE ?', [$likeTerm]));
            })
            ->orderByRaw('CASE WHEN LOWER(name) LIKE ? THEN 0 ELSE 1 END', [$prefixTerm])
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return list<array{term: string, count: int}>
     */
    public function popular(int $limit = 8): array
    {
        return SearchQuery::query()
            ->orderByDesc('count')
            ->orderByDesc('last_searched_at')
            ->limit($limit)
            ->get(['term', 'count'])
            ->map(fn (SearchQuery $searchQuery): array => [
                'term' => $searchQuery->term,
                'count' => $searchQuery->count,
            ])
            ->all();
    }

    public function recordSearch(string $query): void
    {
        $normalized = $this->normalizeTerm($query);

        if ($normalized === null) {
            return;
        }

        $now = now();

        SearchQuery::query()->upsert(
            [[
                'term' => $normalized,
                'count' => 1,
                'last_searched_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['term'],
            [
                'count' => DB::raw('search_queries.count + 1'),
                'last_searched_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    private function normalizeTerm(string $query): ?string
    {
        $normalized = mb_strtolower(trim($query), 'UTF-8');

        if ($normalized === '') {
            return null;
        }

        if (mb_strlen($normalized) < 2) {
            return null;
        }

        return mb_substr($normalized, 0, 100);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
