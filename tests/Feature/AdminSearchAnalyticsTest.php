<?php

use App\Models\SearchQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns search analytics for platform admin', function () {
    SearchQuery::query()->create([
        'term' => 'kulaklık',
        'count' => 25,
        'last_searched_at' => now()->subDay(),
    ]);

    SearchQuery::query()->create([
        'term' => 'hoparlör',
        'count' => 10,
        'last_searched_at' => now()->subDays(10),
    ]);

    SearchQuery::query()->create([
        'term' => 'mouse',
        'count' => 4,
        'last_searched_at' => now()->subHours(2),
    ]);

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->getJson('/api/admin/search-analytics')
        ->assertOk()
        ->assertJsonPath('analytics.summary.total_searches', 39)
        ->assertJsonPath('analytics.summary.unique_terms', 3)
        ->assertJsonPath('analytics.summary.active_terms_last_7_days', 2)
        ->assertJsonPath('analytics.top_terms.0.term', 'kulaklık')
        ->assertJsonPath('analytics.top_terms.0.count', 25)
        ->assertJsonPath('analytics.top_terms.1.term', 'hoparlör');
});

it('filters search analytics by recent days', function () {
    SearchQuery::query()->create([
        'term' => 'yeni arama',
        'count' => 3,
        'last_searched_at' => now()->subDay(),
    ]);

    SearchQuery::query()->create([
        'term' => 'eski arama',
        'count' => 50,
        'last_searched_at' => now()->subDays(30),
    ]);

    $this->withToken(User::factory()->admin()->create()->createToken('test')->plainTextToken)
        ->getJson('/api/admin/search-analytics?days=7')
        ->assertOk()
        ->assertJsonCount(1, 'analytics.top_terms')
        ->assertJsonPath('analytics.top_terms.0.term', 'yeni arama');
});

it('forbids vendors from viewing search analytics', function () {
    $this->withToken(User::factory()->vendor()->create()->createToken('test')->plainTextToken)
        ->getJson('/api/admin/search-analytics')
        ->assertForbidden();
});

it('forbids guests from viewing search analytics', function () {
    $this->getJson('/api/admin/search-analytics')
        ->assertUnauthorized();
});
