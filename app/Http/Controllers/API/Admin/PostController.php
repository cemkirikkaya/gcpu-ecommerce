<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Http\Resources\Api\AdminPostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::query()
            ->with('user:id,name')
            ->latest('updated_at')
            ->get();

        return response()->json([
            'posts' => AdminPostResource::collection($posts),
        ]);
    }

    public function show(Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        $post->load('user:id,name');

        return response()->json([
            'post' => new AdminPostResource($post),
        ]);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        $post = Post::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $post->load('user:id,name');

        return response()->json([
            'post' => new AdminPostResource($post),
            'message' => 'Blog yazısı oluşturuldu.',
        ], 201);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $post->update($request->validated());
        $post->load('user:id,name');

        return response()->json([
            'post' => new AdminPostResource($post->fresh()),
            'message' => 'Blog yazısı güncellendi.',
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json([
            'message' => 'Blog yazısı silindi.',
        ]);
    }
}
