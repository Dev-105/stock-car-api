<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\LigneCommande;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Get user's orders
    public function index(Request $request)
    {
        $orders = Command::with(['lignes.product.images', 'promoCode'])
                        ->where('user_id', $request->user()->id)
                        ->orderBy('created_at', 'desc')
                        ->get();
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
    
    // Create new order from cart
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Get user's cart items
            $cartItems = CartItem::with('product')
                                ->where('user_id', $request->user()->id)
                                ->get();
            
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty'
                ], 400);
            }
            
            // Validate stock and calculate total
            $total = 0;
            $orderItems = [];
            
            foreach ($cartItems as $item) {
                if ($item->product->stock < $item->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$item->product->title}. Only {$item->product->stock} available."
                    ], 400);
                }
                
                $finalPrice = $item->product->final_price;
                $subtotal = $finalPrice * $item->quantity;
                $total += $subtotal;
                
                $orderItems[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $finalPrice,
                    'subtotal' => $subtotal
                ];
            }
            
            // Apply promo code if provided
            $promoCode = null;
            if ($request->has('promo_code')) {
                $promoCode = PromoCode::where('code', $request->promo_code)->first();
                
                if ($promoCode && $promoCode->is_valid) {
                    $discount = $promoCode->calculateDiscount($total);
                    $total -= $discount;
                    $promoCode->use();
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired promo code'
                    ], 400);
                }
            }
            
            // Create order
            $order = Command::create([
                'user_id' => $request->user()->id,
                'total' => $total,
                'status' => 'pending',
                'promo_code_id' => $promoCode ? $promoCode->id : null
            ]);
            
            // Create order lines
            foreach ($orderItems as $item) {
                LigneCommande::create([
                    'command_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal']
                ]);
                
                // Reduce stock
                $product = Product::find($item['product_id']);
                $product->reduceStock($item['quantity']);
            }
            
            // Clear cart
            CartItem::where('user_id', $request->user()->id)->delete();
            
            // Add fidelity points (1 point per 10€ spent)
            $pointsEarned = floor($total / 10);
            if ($pointsEarned > 0) {
                $request->user()->addFidelityPoints($pointsEarned);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order->load('lignes.product'),
                'points_earned' => $pointsEarned
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating order: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get single order
    public function show(Request $request, $id)
    {
        $order = Command::with(['lignes.product.images', 'promoCode'])
                       ->where('user_id', $request->user()->id)
                       ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
    
    // Cancel order
    public function cancel(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $order = Command::where('user_id', $request->user()->id)
                           ->findOrFail($id);
            
            if ($order->cancel()) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Order cancelled successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled'
            ], 400);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling order: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Admin: Get all orders
    public function adminIndex(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $orders = Command::with(['user', 'lignes.product.images', 'promoCode'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
    
    // Admin: Update order status
    public function updateStatus(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);
        
        $order = Command::findOrFail($id);
        $order->status = $validated['status'];
        $order->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Order status updated',
            'order' => $order
        ]);
    }

    // Admin: Get dashboard stats
    public function getStats(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $totalRevenue = Command::where('status', 'completed')->sum('total');
        $totalOrders = Command::count();
        $totalUsers = \App\Models\User::count();
        $totalProducts = \App\Models\Product::count();
        
        $recentOrders = Command::with('user')->orderBy('created_at', 'desc')->take(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => (float)$totalRevenue,
                'total_orders' => $totalOrders,
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
                'recent_orders' => $recentOrders
            ]
        ]);
    }
}