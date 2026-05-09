<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register new user
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'phone' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255'
            ]);
            
            $validated['password'] = Hash::make($validated['password']);
            $validated['role'] = 'user';
            $validated['fidelity_points'] = 0;
            
            $user = User::create($validated);
            
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }
    }
    
    // Login user
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);
            
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid login credentials'
                ], 401);
            }
            
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully',
                'user' => $user,
                'token' => $token
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }
    }
    
    // Logout user
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
    
    // Get authenticated user
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }
    
    // Refresh token
    public function refresh(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $newToken = $request->user()->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'token' => $newToken
        ]);
    }
}