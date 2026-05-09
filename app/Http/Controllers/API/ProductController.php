<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // List all products with filters
    public function index(Request $request)
    {
        $query = Product::with('images', 'tags', 'reviews.user');
        
        // Filter by theme
        if ($request->has('theme')) {
            $query->where('theme', 'like', '%' . $request->theme . '%');
        }
        
        // Filter by min price
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        // Filter by max price
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        // Filter by tag
        if ($request->has('tag')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }
        
        // Sort
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage);
        
        // Add computed attributes
        $products->getCollection()->transform(function($product) {
            $product->final_price = $product->final_price;
            $product->average_rating = $product->average_rating;
            $product->likes_count = $product->likes_count;
            return $product;
        });
        
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
    
    // Create new product
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'discount_percentage' => 'integer|min:0|max:100',
                'stock' => 'required|integer|min:0',
                'theme' => 'required|string|max:100',
                'tags' => 'nullable|array',
                'tags.*' => 'string|exists:tags,name',
                'images' => 'nullable|array',
                'images.*' => 'file|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            // Create product
            $product = Product::create($validated);
            
            // Attach tags
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
                $product->tags()->sync($tagIds);
            }
            
            // Upload images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $path
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product->load('images', 'tags')
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating product: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Show single product
    public function show($id)
    {
        $product = Product::with('images', 'tags', 'reviews.user', 'likes')
                         ->findOrFail($id);
        
        $product->final_price = $product->final_price;
        $product->average_rating = $product->average_rating;
        $product->likes_count = $product->likes_count;
        
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }
    
    // Update product
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'discount_percentage' => 'sometimes|integer|min:0|max:100',
                'stock' => 'sometimes|integer|min:0',
                'theme' => 'sometimes|string|max:100'
            ]);
            
            $product->update($validated);
            
            // Update tags if provided
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
                $product->tags()->sync($tagIds);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product->load('images', 'tags')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete product
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Delete associated images from storage
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_url);
            }
            
            $product->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Search products by theme
    public function searchByTheme($theme)
    {
        $products = Product::with('images', 'tags')
                          ->where('theme', 'like', "%{$theme}%")
                          ->get();
        
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
    
    // Get products by tag
    public function getByTag($tagName)
    {
        $tag = Tag::where('name', $tagName)->firstOrFail();
        $products = $tag->products()->with('images', 'tags')->get();
        
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
    
    // Add product image
    public function addImage(Request $request, $id)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            $product = Product::findOrFail($id);
            
            $path = $request->file('image')->store('products', 'public');
            
            $image = ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $path
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Image added successfully',
                'image' => $image
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding image: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Delete product image
    public function deleteImage($imageId)
    {
        try {
            $image = ProductImage::findOrFail($imageId);
            
            Storage::disk('public')->delete($image->image_url);
            $image->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting image: ' . $e->getMessage()
            ], 500);
        }
    }
}