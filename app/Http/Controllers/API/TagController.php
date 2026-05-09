<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    // List all tags
    public function index()
    {
        $tags = Tag::withCount('products')->get();
        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    // Create a tag
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:tags|max:50'
        ]);

        $tag = Tag::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully',
            'data' => $tag
        ], 201);
    }

    // Delete a tag
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $tag = Tag::findOrFail($id);
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully'
        ]);
    }
}
