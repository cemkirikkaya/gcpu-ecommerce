<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminPostToken(): string
{
    return User::factory()->admin()->create()->createToken('test')->plainTextToken;
}

it('lets admins list all posts including drafts', function () {
    $author = User::factory()->admin()->create(['name' => 'Editör']);

    Post::factory()->for($author)->published()->create(['title' => 'Yayında']);
    Post::factory()->for($author)->draft()->create(['title' => 'Taslak']);

    $response = $this->withToken(adminPostToken())
        ->getJson('/api/admin/posts')
        ->assertOk()
        ->assertJsonCount(2, 'posts');

    $titles = collect($response->json('posts'))->pluck('title')->all();

    expect($titles)->toContain('Yayında', 'Taslak');
});

it('lets admins create update and delete posts', function () {
    $token = adminPostToken();

    $createResponse = $this->withToken($token)
        ->postJson('/api/admin/posts', [
            'title' => 'Yeni Blog Yazısı',
            'slug' => 'yeni-blog-yazisi',
            'excerpt' => 'Kısa özet',
            'content' => '<p>Merhaba blog</p>',
            'published_at' => now()->toISOString(),
        ])
        ->assertCreated()
        ->assertJsonPath('post.slug', 'yeni-blog-yazisi');

    $postId = $createResponse->json('post.id');

    $this->withToken($token)
        ->putJson("/api/admin/posts/{$postId}", [
            'title' => 'Güncellenmiş Yazı',
            'slug' => 'guncellenmis-yazi',
            'excerpt' => 'Yeni özet',
            'content' => '<p>Güncellendi</p>',
            'published_at' => now()->toISOString(),
        ])
        ->assertOk()
        ->assertJsonPath('post.title', 'Güncellenmiş Yazı');

    $this->withToken($token)
        ->deleteJson("/api/admin/posts/{$postId}")
        ->assertOk();

    $this->assertDatabaseMissing('posts', ['id' => $postId]);
});

it('forbids vendors from managing blog posts', function () {
    $vendorToken = User::factory()->vendor()->create()->createToken('test')->plainTextToken;

    $this->withToken($vendorToken)
        ->getJson('/api/admin/posts')
        ->assertForbidden();

    $this->withToken($vendorToken)
        ->postJson('/api/admin/posts', [
            'title' => 'Vendor Yazısı',
            'slug' => 'vendor-yazisi',
            'content' => '<p>Test</p>',
        ])
        ->assertForbidden();
});

it('lets admins fetch a draft post by id', function () {
    $author = User::factory()->admin()->create();

    $post = Post::factory()->for($author)->draft()->create([
        'title' => 'Taslak Yazı',
        'slug' => 'taslak-yazi',
    ]);

    $this->withToken(adminPostToken())
        ->getJson("/api/admin/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('post.title', 'Taslak Yazı')
        ->assertJsonPath('post.is_published', false);
});

it('shows created posts on the public blog api when published', function () {
    $token = adminPostToken();

    $this->withToken($token)
        ->postJson('/api/admin/posts', [
            'title' => 'Vitrin Yazısı',
            'slug' => 'vitrin-yazisi',
            'content' => '<p>Vitrinde görünür</p>',
            'published_at' => now()->subMinute()->toISOString(),
        ])
        ->assertCreated();

    $this->getJson('/api/posts')
        ->assertOk()
        ->assertJsonPath('posts.0.slug', 'vitrin-yazisi');
});
