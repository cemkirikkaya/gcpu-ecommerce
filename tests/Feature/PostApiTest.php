<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only published posts', function () {
    $author = User::factory()->create(['name' => 'Editör']);

    Post::factory()->for($author)->published()->create([
        'title' => 'Yayında Yazı',
        'slug' => 'yayinda-yazi',
        'published_at' => now()->subDay(),
    ]);

    Post::factory()->for($author)->draft()->create([
        'title' => 'Taslak Yazı',
        'slug' => 'taslak-yazi',
    ]);

    Post::factory()->for($author)->published()->create([
        'title' => 'Gelecek Yazı',
        'slug' => 'gelecek-yazi',
        'published_at' => now()->addDay(),
    ]);

    $this->getJson('/api/posts')
        ->assertOk()
        ->assertJsonCount(1, 'posts')
        ->assertJsonPath('posts.0.slug', 'yayinda-yazi')
        ->assertJsonPath('posts.0.author_name', 'Editör')
        ->assertJsonMissing(['content']);
});

it('shows a published post by slug', function () {
    $author = User::factory()->create(['name' => 'Editör']);

    Post::factory()->for($author)->published()->create([
        'title' => 'Detay Yazısı',
        'slug' => 'detay-yazisi',
        'content' => '<p>Merhaba blog</p>',
    ]);

    $this->getJson('/api/posts/detay-yazisi')
        ->assertOk()
        ->assertJsonPath('post.title', 'Detay Yazısı')
        ->assertJsonPath('post.content', '<p>Merhaba blog</p>');
});

it('returns not found for draft posts', function () {
    Post::factory()->draft()->create([
        'slug' => 'gizli-yazi',
    ]);

    $this->getJson('/api/posts/gizli-yazi')
        ->assertNotFound();
});

it('paginates blog posts', function () {
    $author = User::factory()->create();

    Post::factory()
        ->for($author)
        ->published()
        ->count(13)
        ->create();

    $this->getJson('/api/posts?page=2')
        ->assertOk()
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonCount(1, 'posts');
});
