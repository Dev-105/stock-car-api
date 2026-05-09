<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Get user's cart
    public function index(Request $request)
    {
        $cartItems = CartItem::with('product.images')
                            ->where('user_id', $request->user()->id)
                            ->get();
        
        $total = 0;
        $itemsCount = 0;
        
        foreach ($cartItems as $item) {
            $item->subtotal = $item->subtotal;
            $item->product->final_price = $item->product->final_price;
            $total += $item->subtotal;
            $itemsCount += $item->quantity;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cartItems,
                'total' => round($total, 2),
                'items_count' => $itemsCount,
                'formatted_total' => number_format($total, 2) . ' €'
            ]
        ]);
    }
    
    // Add item to cart
    public function addToCart(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);
            
            $product = Product::findOrFail($validated['product_id']);
            
            // Check stock
            if ($product->stock < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Only ' . $product->stock . ' items available.'
                ], 400);
            }
            
            $cartItem = CartItem::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'product_id' => $validated['product_id']
                ],
                [
                    'quantity' => $validated['quantity']
                ]
            );
            
            $cartItem->load('product.images');
            $cartItem->subtotal = $cartItem->subtotal;
            
            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'cart_item' => $cartItem
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding item to cart: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Update cart item quantity
    public function updateQuantity(Request $request, $productId)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);
            
            $cartItem = CartItem::where('user_id', $request->user()->id)
                               ->where('product_id', $productId)
                               ->firstOrFail();
            
            $product = Product::findOrFail($productId);
            
            // Check stock
            if ($product->stock < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Only ' . $product->stock . ' items available.'
                ], 400);
            }
            
            $cartItem->quantity = $validated['quantity'];
            $cartItem->save();
            
            $cartItem->load('product.images');
            $cartItem->subtotal = $cartItem->subtotal;
            
            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully',
                'cart_item' => $cartItem
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating cart: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Remove item from cart
    public function removeFromCart(Request $request, $productId)
    {
        try {
            $deleted = CartItem::where('user_id', $request->user()->id)
                              ->where('product_id', $productId)
                              ->delete();
            
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item removed from cart successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing item: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Clear entire cart
    public function clearCart(Request $request)
    {
        try {
            CartItem::where('user_id', $request->user()->id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cart: ' . $e->getMessage()
            ], 500);
        }
    }
}