<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // Store a new review
    public function store(Request $request, $productId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000'
        ]);

        $product = Product::findOrFail($productId);

        // Check if user already reviewed this product
        $existingReview = Review::where('user_id', $request->user()->id)
                                ->where('product_id', $productId)
                                ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product'
            ], 400);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $productId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'data' => $review->load('user')
        ], 201);
    }

    // Delete a review (user can delete their own, admin can delete any)
    public function destroy(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        if ($review->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }
}
