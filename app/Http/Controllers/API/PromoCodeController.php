<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    // Verify promo code
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);
        
        $promoCode = PromoCode::where('code', $request->code)->first();
        
        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Promo code not found'
            ], 404);
        }
        
        if (!$promoCode->is_valid) {
            return response()->json([
                'success' => false,
                'message' => 'Promo code is invalid or expired'
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'discount_percentage' => $promoCode->discount_percentage,
            'expires_at' => $promoCode->expires_at
        ]);
    }
    
    // Admin: Create promo code
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validated = $request->validate([
            'code' => 'required|string|unique:promo_codes|max:50',
            'discount_percentage' => 'required|integer|min:1|max:100',
            'max_usage' => 'required|integer|min:1',
            'expires_at' => 'required|date|after:now'
        ]);
        
        $promoCode = PromoCode::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Promo code created',
            'data' => $promoCode
        ], 201);
    }
    
    // Admin: List all promo codes
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $promoCodes = PromoCode::orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $promoCodes
        ]);
    }
    
    // Admin: Delete promo code
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $promoCode = PromoCode::findOrFail($id);
        $promoCode->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Promo code deleted'
        ]);
    }
}