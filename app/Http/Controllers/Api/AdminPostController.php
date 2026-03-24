<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPostController extends Controller
{
    /**
     * List posts with optional filters.
     */
    public function index(Request $request)
    {
        $query = Post::query()->with(['author', 'tags']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            }
        }

        if ($authorId = $request->query('author_id')) {
            $query->where('user_id', $authorId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('published_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('published_at', '<=', $to);
        }

        $perPage = (int) $request->query('per_page', 15);

        $posts = $query
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return PostResource::collection($posts);
    }

    /**
     * Store a new post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:posts,slug'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['required', 'array'],
            'featured_image' => ['nullable', 'string', 'max:2048'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $validated['is_published'] = $validated['is_published'] ?? false;

        $validated['user_id'] = $request->user()->id;

        $post = Post::create($validated);

        if (!empty($validated['tag_ids'])) {
            $post->tags()->sync($validated['tag_ids']);
        }

        return new PostResource($post->load(['author', 'tags']));
    }

    /**
     * Show a single post.
     */
    public function show(Post $post)
    {
        return new PostResource($post->load(['author', 'tags']));
    }

    /**
     * Update an existing post.
     */
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')->ignore($post->id),
            ],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'body' => ['sometimes', 'array'],
            'featured_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo_description' => ['sometimes', 'nullable', 'string'],
            'tag_ids' => ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $post->update($validated);

        if ($request->has('tag_ids')) {
            $post->tags()->sync($validated['tag_ids'] ?? []);
        }

        return new PostResource($post->fresh()->load(['author', 'tags']));
    }

    /**
     * Delete a post.
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json(null, 204);
    }
}

