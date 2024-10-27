<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    
    public function index(Request $request)
    {
        return $request->user()->posts()->orderBy('pinned', 'desc')->get();
    }

 
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'cover_image' => 'required|image',
            'pinned' => 'required|boolean',
            'tags' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $path = $request->file('cover_image')->store('covers');

        $post = $request->user()->posts()->create([
            'title' => $request->title,
            'body' => $request->body,
            'cover_image' => $path,
            'pinned' => $request->pinned,
        ]);

        if ($request->tags) {
            $post->tags()->attach($request->tags);
        }

        return response()->json($post, 201);
    }

 
    public function show(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($post);
    }

   
    public function update(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'cover_image' => 'image',
            'pinned' => 'required|boolean',
            'tags' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->hasFile('cover_image')) {
            Storage::delete($post->cover_image);
            $path = $request->file('cover_image')->store('covers');
            $post->cover_image = $path;
        }

        $post->update([
            'title' => $request->title,
            'body' => $request->body,
            'pinned' => $request->pinned,
        ]);

        if ($request->tags) {
            $post->tags()->sync($request->tags);
        }

        return response()->json($post);
    }

   
    public function destroy(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    
    public function trashed(Request $request)
    {
        return $request->user()->posts()->onlyTrashed()->get();
    }

  
    public function restore(Request $request, $post_id)
    {
        $post = $request->user()->posts()->onlyTrashed()->where('id', $post_id)->first();

        if ($post) {
            $post->restore();
            return response()->json(['message' => 'Post restored successfully']);
        }

        return response()->json(['message' => 'Post not found'], 404);
    }
}
