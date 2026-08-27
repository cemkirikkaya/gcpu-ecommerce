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

    /**
     * @return array{
     *     summary: array{total_searches: int, unique_terms: int, active_terms_last_7_days: int},
     *     top_terms: list<array{term: string, count: int, last_searched_at: string|null}>
     * }
     */
    public function analytics(int $limit = 20, ?int $days = null): array
    {
        $totalSearches = (int) SearchQuery::query()->sum('count');
        $uniqueTerms = SearchQuery::query()->count();
        $activeTermsLast7Days = SearchQuery::query()
            ->where('last_searched_at', '>=', now()->subDays(7))
            ->count();

        $topTermsQuery = SearchQuery::query()
            ->orderByDesc('count')
            ->orderByDesc('last_searched_at');

        if ($days !== null) {
            $topTermsQuery->where('last_searched_at', '>=', now()->subDays($days));
        }

        $topTerms = $topTermsQuery
            ->limit($limit)
            ->get(['term', 'count', 'last_searched_at'])
            ->map(fn (SearchQuery $searchQuery): array => [
                'term' => $searchQuery->term,
                'count' => $searchQuery->count,
                'last_searched_at' => $searchQuery->last_searched_at?->toIso8601String(),
            ])
            ->all();

        return [
            'summary' => [
                'total_searches' => $totalSearches,
                'unique_terms' => $uniqueTerms,
                'active_terms_last_7_days' => $activeTermsLast7Days,
            ],
            'top_terms' => $topTerms,
        ];
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
