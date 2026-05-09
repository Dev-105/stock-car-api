<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // Get user profile
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Load relationships
        $user->load(['likes.product.images', 'reviews', 'commands']);
        
        // Add computed attributes
        $user->total_orders = $user->commands->count();
        $user->total_spent = $user->commands->sum('total');
        $user->likes_count = $user->likes->count();
        $user->reviews_count = $user->reviews->count();
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
    
    // Update user profile
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
                'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
                'current_password' => 'required_with:new_password|string',
                'new_password' => 'nullable|string|min:6|confirmed'
            ]);
            
            // Update password if provided
            if ($request->has('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect'
                    ], 400);
                }
                $validated['password'] = Hash::make($request->new_password);
            }
            
            // Remove password fields from validation array
            unset($validated['current_password'], $validated['new_password']);
            
            $user->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update profile image
    public function updateProfileImage(Request $request)
    {
        try {
            $request->validate([
                'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            $user = $request->user();
            
            // Delete old image
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            // Upload new image
            $path = $request->file('profile_image')->store('profiles', 'public');
            $user->profile_image = $path;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Profile image updated',
                'profile_image' => asset('storage/' . $path)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading image: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get user's likes
    public function getLikes(Request $request)
    {
        $likes = $request->user()->likes()->with('product.images')->get();
        
        return response()->json([
            'success' => true,
            'data' => $likes
        ]);
    }
    
    // Toggle like on product
    public function toggleLike(Request $request, $productId)
    {
        $user = $request->user();
        $existingLike = $user->likes()->where('product_id', $productId)->first();
        
        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
            $message = 'Product unliked';
        } else {
            $user->likes()->create(['product_id' => $productId]);
            $liked = true;
            $message = 'Product liked';
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'liked' => $liked,
            'likes_count' => Product::find($productId)->likes()->count()
        ]);
    }
    
    // Get user's fidelity points
    public function getFidelityPoints(Request $request)
    {
        return response()->json([
            'success' => true,
            'fidelity_points' => $request->user()->fidelity_points
        ]);
    }
    
    // Admin: List all users
    public function adminIndex(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $users = User::withCount(['likes', 'reviews', 'commands'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
    
    // Admin: Update user role
    public function updateRole(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validated = $request->validate([
            'role' => 'required|in:admin,user'
        ]);
        
        $user = User::findOrFail($id);
        $user->role = $validated['role'];
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'User role updated',
            'user' => $user
        ]);
    }
    
    // Admin: Delete user
    public function adminDestroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $user = User::findOrFail($id);
        
        // Don't allow deleting yourself
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 400);
        }
        
        $user->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}